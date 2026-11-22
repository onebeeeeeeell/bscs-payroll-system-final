<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Payroll;
use App\Models\PayrollPeriod;

class DashboardService
{
    // order of the pay period status list on the dashboard
    private const PERIOD_STATUSES = ['Pending', 'Processing', 'Paid', 'Failed'];

    /**
     * All the data of the dashboard page. The dashboard has no user input
     * (no search or filters), so there is nothing to sanitize here yet.
     * All queries are in the model scopes, this only calls them.
     */
    public function data(): array
    {
        // one aggregate query: payroll count + gross, deductions and net totals
        $summary = Payroll::query()->active()->summary()->first();

        // one grouped query, then fill the missing statuses with 0
        $periodTotals = PayrollPeriod::query()->countsByStatus()->get()->pluck('total', 'status');

        $periodStatuses = collect(self::PERIOD_STATUSES)
            ->map(fn (string $status) => [
                'status' => $status,
                'total' => (int) ($periodTotals[$status] ?? 0),
            ])
            ->values();

        // one query: active employees per department
        $departments = Department::query()
            ->withActiveEmployeesCount()
            ->alphabetical()
            ->get();

        return [
            'stats' => [
                // every employee has a department, so the sum is the active total (no extra query)
                'active_employees' => (int) $departments->sum('employees_count'),
                'pending_periods' => (int) ($periodTotals['Pending'] ?? 0),
                'payrolls' => (int) $summary->payrolls,
                'awaiting_payslip' => Payroll::query()->active()->withoutPayslip()->count(),
                'gross_pay' => (float) $summary->gross_pay,
                'total_deductions' => (float) $summary->total_deductions,
                'net_pay' => (float) $summary->net_pay,
            ],
            'recent_payrolls' => Payroll::query()->active()->recent()->get(),
            'period_statuses' => $periodStatuses,
            'departments' => $departments,
        ];
    }
}