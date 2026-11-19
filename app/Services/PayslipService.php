<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ContributionRate;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Services\Concerns\SanitizesText;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class PayslipService
{
    use SanitizesText;

    /**
     * @param array<string, mixed> $filters search, status, date, department_id, position_id
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return Payslip::query()
            ->withDetails()
            ->forFilters([...$filters, 'search' => $this->sanitizeSearch($filters['search'] ?? null)])
            ->latest('date_generated')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString(); // keep the filters when changing pages
    }

    public function create(array $data): Payslip
    {
        // transaction, so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => Payslip::create($data)
        );
    }

    public function update(Payslip $payslip, array $data): Payslip
    {
        return DB::transaction(function () use ($payslip, $data) {
            $payslip->update($data);

            return $payslip;
        });
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
        $payslip->load([
            'payroll.employee:id,first_name,last_name,suffix,position_id,department_id,daily_rate',
            'payroll.employee.position:id,title',
            'payroll.employee.department:id,department_name',
            'payroll.payrollPeriod:id,start_date,end_date,pay_date,status',
            'payroll.user:id,name',
            'payroll.deductions.contributionRate:id,contribution_type_id,calculation_type,rate',
            'payroll.deductions.contributionRate.contributionType:id,name',
        ]);

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

    // diriiiiiiiiiiiiiiiiiiiii sugpon if may ano sa controller na logic
}