<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\Cache;

class NotificationService
{
    // saglit lang, para hindi mag-count query sa bawat page load at bell open
    private const COUNT_TTL_SECONDS = 30;

    private static function missingAttendanceKey(): string
    {
        return 'notifications:missing-attendance:'.today()->toDateString();
    }

    private function missingAttendanceCount(): int
    {
        return Cache::remember(
            self::missingAttendanceKey(),
            self::COUNT_TTL_SECONDS,
            fn () => Employee::active()
                ->missingAttendanceOn(today()->toDateString())
                ->count(),
        );
    }

    /*
     * LABEL: Flush missing attendance count
     * Purpose: Tawagin pag may nagbago sa attendance o sa active employees
     * (create, update, delete, restore, punch, archive) para hindi lumang
     * bilang ang makita sa badge.
     */
    public static function flushMissingAttendanceCount(): void
    {
        Cache::forget(self::missingAttendanceKey());
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