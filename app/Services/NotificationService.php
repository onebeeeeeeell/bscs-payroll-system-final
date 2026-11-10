<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use Illuminate\Support\Carbon;

class NotificationService
{
    /**
     * Pull the raw numbers once so the bell and the sidebar badges always agree.
     *
     * @return array{missing_attendance: int, ready_periods: \Illuminate\Support\Collection, failed_periods: \Illuminate\Support\Collection}
     */
    private function summary(): array
    {
        $today = today();

        // Active employees (not archived) with no attendance record for today
        $missingAttendance = Employee::whereNull('archived_at')
            ->whereDoesntHave('attendances', fn ($query) => $query->whereDate('work_date', $today))
            ->count();

        // Pay periods that already ended but are still waiting to be processed
        $readyPeriods = PayrollPeriod::where('status', 'Pending')
            ->whereDate('end_date', '<=', $today)
            ->orderBy('end_date')
            ->get();

        $failedPeriods = PayrollPeriod::where('status', 'Failed')->orderBy('end_date')->get();

        return [
            'missing_attendance' => $missingAttendance,
            'ready_periods' => $readyPeriods,
            'failed_periods' => $failedPeriods,
        ];
    }

    /** Small counts used for the sidebar badges (shared on every page). */
    public function counts(): array
    {
        $summary = $this->summary();

        return [
            'attendance' => $summary['missing_attendance'],
            'payrolls' => $summary['ready_periods']->count() + $summary['failed_periods']->count(),
        ];
    }

    /** Full list shown inside the bell popover. */
    public function items(): array
    {
        $summary = $this->summary();
        $items = [];

        if ($summary['missing_attendance'] > 0) {
            $count = $summary['missing_attendance'];
            $items[] = [
                'id' => 'missing-attendance',
                'type' => 'warning',
                'title' => $count === 1 ? '1 employee has no attendance today' : "{$count} employees have no attendance today",
                'description' => 'Record attendance before running payroll.',
                'href' => route('attendance.index'),
            ];
        }

        foreach ($summary['ready_periods'] as $period) {
            $items[] = [
                'id' => "period-ready-{$period->id}",
                'type' => 'info',
                'title' => 'Payroll is ready to process',
                'description' => $this->periodLabel($period).' has ended and is still pending.',
                'href' => route('payrolls.index'),
            ];
        }

        foreach ($summary['failed_periods'] as $period) {
            $items[] = [
                'id' => "period-failed-{$period->id}",
                'type' => 'error',
                'title' => 'Payroll run failed',
                'description' => $this->periodLabel($period).' needs to be checked.',
                'href' => route('payrolls.index'),
            ];
        }

        return $items;
    }

    private function periodLabel(PayrollPeriod $period): string
    {
        return Carbon::parse($period->start_date)->format('M j').' – '.Carbon::parse($period->end_date)->format('M j, Y');
    }
}