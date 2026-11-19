<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ContributionRate;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Services\Concerns\SanitizesText;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayrollProcessingService
{
    use SanitizesText;

    private const STANDARD_WORK_HOURS = 8;

    private const OVERTIME_MULTIPLIER = 1.25;

    public const STATUS_READY = 'ready';

    public const STATUS_NO_ATTENDANCE = 'no_attendance';

    public const STATUS_OPEN_TIME_OUT = 'open_time_out';

    public const STATUS_DEDUCTIONS_EXCEED_GROSS = 'deductions_exceed_gross';

    // Hinaharang ang pag-save hangga't may ganitong row. Ang no_attendance
    // ay hindi block: hindi lang siya isasama sa payroll.
    private const BLOCKING_STATUSES = [
        self::STATUS_OPEN_TIME_OUT,
        self::STATUS_DEDUCTIONS_EXCEED_GROSS,
    ];

    /*
     * LABEL: Process page data
     * Purpose: Data ng payroll/create page. Ang dropdown ng periods ay isang
     * query; ang napiling period ay hinahanap sa loob ng collection na 'yon
     * (walang dagdag na query). Ang preview ay walang sine-save sa DB.
     *
     * @param array{payroll_period_id?: int|string|null, search?: string|null} $validated
     */
    public function pageData(array $validated): array
    {
        $periods = PayrollPeriod::query()->processable()->get();
        $periodId = $validated['payroll_period_id'] ?? null;
        $search = $this->sanitizeSearch($validated['search'] ?? null);
        $preview = null;

        if ($periodId !== null && $periodId !== '') {
            $period = $periods->firstWhere('id', (int) $periodId);

            if (! $period) {
                throw ValidationException::withMessages([
                    'payroll_period_id' => 'This payroll period is not available for processing.',
                ]);
            }

            $preview = $this->preview($period, $search);
        }

        return [
            'periods' => $periods,
            'preview' => $preview,
            'filters' => [
                'payroll_period_id' => $periodId !== null ? (string) $periodId : '',
                'search' => $search,
            ],
        ];
    }

    /*
     * LABEL: Save payroll
     * Purpose: I-save ang payroll ng buong period sa iisang transaction.
     * - Nilo-lock ang period para hindi sabay ma-process ng dalawang user.
     * - Server ang nagko-compute ulit (walang amounts na galing sa request).
     * - Bulk insert ang payrolls at deductions (3 queries kahit ilan ang employee).
     * - Pagkatapos, status ng period = Processing.
     */
    public function process(int $periodId, int $userId): PayrollPeriod
    {
        return DB::transaction(function () use ($periodId, $userId) {
            $period = PayrollPeriod::query()->lockById($periodId)->first();

            if (! $period || ! $period->isProcessable()) {
                throw ValidationException::withMessages([
                    'payroll_period_id' => 'This payroll period is not available for processing.',
                ]);
            }

            if (Payroll::query()->forPeriod($period->id)->exists()) {
                throw ValidationException::withMessages([
                    'payroll_period_id' => 'This payroll period already has payroll records.',
                ]);
            }

            ['rows' => $rows, 'rates' => $rates] = $this->buildRows($period);

            if ($rows->whereIn('status', self::BLOCKING_STATUSES)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'payroll_period_id' => 'Resolve the flagged employees before saving this payroll.',
                ]);
            }

            $ready = $rows->where('status', self::STATUS_READY);

            if ($ready->isEmpty()) {
                throw ValidationException::withMessages([
                    'payroll_period_id' => 'No employees have attendance in this period.',
                ]);
            }

            $now = now();

            Payroll::insert($ready->map(fn (array $row) => [
                'employee_id' => $row['employee_id'],
                'payroll_period_id' => $period->id,
                'user_id' => $userId,
                'gross_pay' => $row['gross_pay'],
                'total_deductions' => $row['total_deductions'],
                'net_pay' => $row['net_pay'],
                'date_processed' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());

            $payrollIds = Payroll::query()->forPeriod($period->id)->pluck('id', 'employee_id');

            Deduction::insert($ready->flatMap(
                fn (array $row) => $rates->map(fn (ContributionRate $rate) => [
                    'payroll_id' => $payrollIds[$row['employee_id']],
                    'contribution_rate_id' => $rate->id,
                    'amount' => $rate->rate,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]),
            )->values()->all());

            $period->update(['status' => 'Processing']);

            return $period;
        });
    }

    /**
     * @return array{rows: list<array<string, mixed>>, summary: array<string, int|float>}
     */
    private function preview(PayrollPeriod $period, string $search): array
    {
        $rows = $this->buildRows($period)['rows'];
        $ready = $rows->where('status', self::STATUS_READY);

        $summary = [
            'employees' => $rows->count(),
            'ready' => $ready->count(),
            'no_attendance' => $rows->where('status', self::STATUS_NO_ATTENDANCE)->count(),
            'blocked' => $rows->whereIn('status', self::BLOCKING_STATUSES)->count(),
            'gross_pay' => round($ready->sum('gross_pay'), 2),
            'total_deductions' => round($ready->sum('total_deductions'), 2),
            'net_pay' => round($ready->sum('net_pay'), 2),
        ];

        // summary ay para sa buong period; ang search ay sa table lang
        if ($search !== '') {
            $rows = $rows->filter(fn (array $row) => Str::contains($row['name'], $search, ignoreCase: true));
        }

        return [
            'rows' => $rows->values()->all(),
            'summary' => $summary,
        ];
    }

    /*
     * LABEL: Compute rows
     * Purpose: Tatlong query lang kahit ilan ang employee: active employees,
     * attendance totals ng period (grouped), at latest rate ng bawat type.
     * - paid_days at overtime_hours galing sa Attendance::totalsPerEmployee().
     * - gross = paid_days x daily_rate + OT hours x hourly rate x multiplier.
     * - deductions = flat na kabuuan ng latest contribution rates.
     *
     * @return array{rows: Collection<int, array<string, mixed>>, rates: Collection<int, ContributionRate>}
     */
    private function buildRows(PayrollPeriod $period): array
    {
        $employees = Employee::query()->forPayroll()->get();

        $totals = Attendance::query()
            ->betweenDates($period->start_date, $period->end_date)
            ->totalsPerEmployee(self::STANDARD_WORK_HOURS)
            ->get()
            ->keyBy('employee_id');

        $rates = ContributionRate::query()->latestPerType()->get(['id', 'rate']);
        $deductions = round((float) $rates->sum('rate'), 2);

        $rows = $employees->map(function (Employee $employee) use ($totals, $deductions) {
            $attendance = $totals->get($employee->id);
            $paidDays = (float) ($attendance->paid_days ?? 0);
            $overtimeHours = (float) ($attendance->overtime_hours ?? 0);
            $dailyRate = (float) $employee->daily_rate;

            $gross = round(
                $paidDays * $dailyRate
                + $overtimeHours * ($dailyRate / self::STANDARD_WORK_HOURS) * self::OVERTIME_MULTIPLIER,
                2,
            );

            $status = match (true) {
                (int) ($attendance->open_records ?? 0) > 0 => self::STATUS_OPEN_TIME_OUT,
                $gross <= 0 => self::STATUS_NO_ATTENDANCE,
                $gross < $deductions => self::STATUS_DEDUCTIONS_EXCEED_GROSS,
                default => self::STATUS_READY,
            };

            $payable = $status === self::STATUS_READY;

            return [
                'employee_id' => $employee->id,
                'name' => trim(implode(' ', array_filter([$employee->first_name, $employee->last_name, $employee->suffix]))),
                'paid_days' => $paidDays,
                'overtime_hours' => $overtimeHours,
                'gross_pay' => $gross,
                'total_deductions' => $payable ? $deductions : 0.0,
                'net_pay' => $payable ? round($gross - $deductions, 2) : 0.0,
                'status' => $status,
            ];
        });

        return ['rows' => $rows, 'rates' => $rates];
    }

    private function sanitizeSearch(?string $search): string
    {
        return $this->sanitizeStringFields(['search' => $search], ['search'])['search'] ?? '';
    }
}