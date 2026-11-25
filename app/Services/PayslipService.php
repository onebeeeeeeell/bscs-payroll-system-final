<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ContributionRate;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Position;
use App\Models\Payslip;
use App\Services\Concerns\SanitizesText;
use App\Support\YearBounds;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PayslipService
{
    use SanitizesText;

    /**
     * One row per employee (the payslips of each employee are on the employee
     * payslips page).
     *
     * @param array<string, mixed> $filters search, status, date, department_id, position_id
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return Payslip::query()
            ->forFilters($this->sanitizeFilters($filters))
            ->summaryPerEmployee()
            ->withEmployee()
            ->latestActivityFirst()
            ->paginate($perPage)
            ->withQueryString(); // keep the filters when changing pages
    }

    public function indexData(array $filters): array
    {
        return [
            'payslips' => $this->paginate($filters),
            'departments' => Department::options(),
            'positions' => Position::options(),
            'filters' => $filters,
            // closure: only computed when the page asks for it (not on filter reloads)
            'dateBounds' => fn () => $this->dateBounds(),
        ];
    }

    /*
     * LABEL: Employee payslips
     * Purpose: Data ng employee payslips page (View sa payslip list): ang
     * employee, ang mga payslip niya, at ang summary cards. Nililinis dito ang
     * filters, nasa Payslip scopes ang mga query. Ang "payslips_count" ng
     * aggregate query ay ginagamit din na total ng paginator.
     *
     * @param array{status?: string|null, date?: string|null} $filters
     */
    public function employeeData(int $employeeId, array $filters): array
    {
        $employee = Employee::query()
            ->withListRelations()
            ->findOrFail($employeeId);

        $filters = $this->sanitizeEmployeeFilters($filters);

        $summary = Payslip::query()
            ->forEmployeeRecords($employee->id, $filters)
            ->totalsSummary()
            ->first();

        $count = (int) $summary->payslips_count;

        return [
            'employee' => $employee,
            'payslips' => Payslip::query()
                ->forEmployeeRecords($employee->id, $filters)
                ->withPayrollPeriod()
                ->latestFirst()
                ->paginate(10, total: $count)
                ->withQueryString(),
            'stats' => [
                'payslips' => $count,
                'total_net_pay' => (float) $summary->total_net_pay,
                'last_generated' => $summary->last_generated,
            ],
            'filters' => $filters,
            // closure: only computed when the page asks for it (not on filter reloads)
            'dateBounds' => fn () => $this->dateBounds($employee),
        ];
    }

    /*
     * LABEL: Archive employee payslips
     * Purpose: I-archive ang lahat ng active payslip ng isang employee na
     * tumutugma sa date filter (lahat kung walang date). ISANG UPDATE lang,
     * walang loop. Ang payroll ay hindi apektado.
     *
     * @param array{date?: string|null} $filters
     */
    public function archiveEmployeePayslips(int $employeeId, array $filters): int
    {
        $employee = Employee::query()->findOrFail($employeeId, ['id']);
        $filters = $this->sanitizeEmployeeFilters($filters);

        return Payslip::query()
            ->forEmployeeRecords($employee->id, ['status' => 'active', 'date' => $filters['date']])
            ->update(['archived_at' => now()]);
    }

    /*
     * LABEL: Restore employee payslips
     * Purpose: Ibalik ang lahat ng archived payslip ng isang employee na
     * tumutugma sa date filter. Walang duplicate check na kailangan: isang
     * payslip lang bawat payroll (unique ang payroll_id).
     *
     * @param array{date?: string|null} $filters
     */
    public function restoreEmployeePayslips(int $employeeId, array $filters): int
    {
        $employee = Employee::query()->findOrFail($employeeId, ['id']);
        $filters = $this->sanitizeEmployeeFilters($filters);

        return Payslip::query()
            ->forEmployeeRecords($employee->id, ['status' => 'archived', 'date' => $filters['date']])
            ->update(['archived_at' => null]);
    }

    /*
     * LABEL: Payroll options
     * Purpose: Mga payroll na wala pang payslip para sa Generate payslip
     * dropdown. Nasa Payroll model (awaitingPayslip scope) na ang query.
     */
    public function payrollOptions(): Collection
    {
        return Payroll::query()->awaitingPayslip()->get();
    }

    /*
     * LABEL: Create payslip
     * Purpose: Ang payroll_id lang ang galing sa user. Ang date_generated ay
     * awtomatikong ang oras ng pag-generate (hindi pinipili).
     */
    public function create(array $data): Payslip
    {
        return Payslip::create([
            'payroll_id' => $data['payroll_id'],
            'date_generated' => now(),
        ]);
    }

    /*
     * LABEL: Archive payslip
     * Purpose: Soft archive; ang payroll at ang payslip row ay hindi binubura.
     */
    public function archive(Payslip $payslip): void
    {
        $payslip->archive();
    }

    public function restore(Payslip $payslip): void
    {
        $payslip->restore();
    }

    /*
     * LABEL: Payslip statement
     * Purpose: Data ng payslip preview page: ang payslip na may payroll,
     * employee, period, at deductions, kasama ang earnings breakdown.
     * - Basic salary = paid days (galing sa attendance ng period) x daily rate.
     * - Overtime pay = OT hours x hourly rate x multiplier (parehong formula ng
     *   PayrollProcessingService).
     * - Kapag na-edit ang gross ng payroll kaya hindi na tugma, may "Adjustment"
     *   line para laging tugma ang kabuuan sa gross pay.
     *
     * @return array{payslip: Payslip, earnings: list<array{label: string, basis: string, amount: float}>, deductions: list<array{label: string, basis: string, amount: float}>}
     */
    public function statement(Payslip $payslip): array
    {
        $payslip->loadStatementDetails();

        $payroll = $payslip->payroll;

        return [
            'payslip' => $payslip,
            'earnings' => $this->earnings($payroll),
            'deductions' => $payroll->deductions->map(function ($deduction) {
                $rate = $deduction->contributionRate;
                $isPercent = $rate?->calculation_type === ContributionRate::TYPE_PERCENT;

                return [
                    'label' => $rate?->contributionType?->name ?? 'Contribution',
                    'basis' => $isPercent ? $this->trimNumber((float) $rate->rate).'% of gross' : 'Fixed',
                    'amount' => round((float) $deduction->amount, 2),
                ];
            })->values()->all(),
        ];
    }

    /**
     * @return list<array{label: string, basis: string, amount: float}>
     */
    private function earnings(Payroll $payroll): array
    {
        $period = $payroll->payrollPeriod;
        $gross = round((float) $payroll->gross_pay, 2);

        $attendance = $period
            ? Attendance::query()
                ->forEmployee($payroll->employee_id)
                ->betweenDates($period->start_date, $period->end_date)
                ->totalsPerEmployee(PayrollProcessingService::STANDARD_WORK_HOURS)
                ->first()
            : null;

        $paidDays = (float) ($attendance->paid_days ?? 0);
        $overtimeHours = (float) ($attendance->overtime_hours ?? 0);
        $dailyRate = (float) ($payroll->employee->daily_rate ?? 0);

        $basic = round($paidDays * $dailyRate, 2);
        $overtime = round(
            $overtimeHours * ($dailyRate / PayrollProcessingService::STANDARD_WORK_HOURS) * PayrollProcessingService::OVERTIME_MULTIPLIER,
            2,
        );
        $adjustment = round($gross - $basic - $overtime, 2);

        // Walang attendance na mahanap (hal. naka-archive): isang Gross pay line na lang
        if ($basic <= 0 && $overtime <= 0) {
            return [['label' => 'Gross pay', 'basis' => 'As processed', 'amount' => $gross]];
        }

        $lines = [['label' => 'Basic salary', 'basis' => $this->trimNumber($paidDays).' days', 'amount' => $basic]];

        if ($overtime > 0) {
            $lines[] = ['label' => 'Overtime pay', 'basis' => $this->trimNumber($overtimeHours).' hours', 'amount' => $overtime];
        }

        if (abs($adjustment) >= 0.01) {
            $lines[] = ['label' => 'Adjustment', 'basis' => 'Manual edit', 'amount' => $adjustment];
        }

        return $lines;
    }

    // 11.00 -> "11", 3.50 -> "3.5"
    private function trimNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /*
     * Year range of the date filter: current year, plus any year that has a
     * payslip (archived ones too), so past payslips can always be found.
     * For one employee, only their own payslips count.
     *
     * @return array{min: string, max: string}
     */
    private function dateBounds(?Employee $employee = null): array
    {
        $dates = Payslip::query()
            ->when($employee, fn ($query) => $query->forEmployee($employee->id))
            ->dateBounds()
            ->first();

        return YearBounds::covering([$dates->first_date, $dates->last_date]);
    }


    // status can only be active or archived, date must be a real Y-m-d (else blank)
    private function sanitizeEmployeeFilters(array $filters): array
    {
        return [
            'status' => ($filters['status'] ?? 'active') === 'archived' ? 'archived' : 'active',
            'date' => $this->sanitizeDate($filters['date'] ?? null),
        ];
    }
}