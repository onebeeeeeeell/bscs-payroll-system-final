<?php

namespace App\Services;

use App\Models\Employee;

class NotificationService
{
    private function missingAttendanceCount(): int
    {
        $today = today();

        return Employee::whereNull('archived_at')
            ->whereDoesntHave('attendances', fn ($query) => $query->whereDate('work_date', $today))
            ->count();
    }

    /** Small counts used for the sidebar badges (shared on every page). */
    public function counts(): array
    {
        return [
            'attendance' => $this->missingAttendanceCount(),
        ];
    }

    /** Full list shown inside the bell popover. */
    public function items(): array
    {
        $items = [];
        $count = $this->missingAttendanceCount();

        if ($count > 0) {
            $items[] = [
                'id' => 'missing-attendance',
                'type' => 'warning',
                'title' => $count === 1 ? '1 employee has no attendance today' : "{$count} employees have no attendance today",
                'description' => 'Record attendance before running payroll.',
                'href' => route('attendance.index'),
            ];
        }

        return $items;
    }
}