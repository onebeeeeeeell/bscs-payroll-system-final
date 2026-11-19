<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/*
 * LABEL: Employee and day filters
 * Purpose: Parehong filter ng Payroll at Payslip index (dating magkaparehong
 * code sa dalawang model): eksaktong araw ng isang timestamp column, at
 * search / department / position sa employee.
 */
trait FiltersListsByEmployeeAndDay
{
    /*
     * LABEL: On day scope
     * Purpose: Lahat ng row na ang $column ay nasa loob ng araw na $date (Y-m-d).
     * Range sa halip na whereDate() para magamit ang index ng column.
     * Walang date = walang filter.
     */
    public function scopeOnDay(Builder $query, string $column, ?string $date): void
    {
        if (blank($date)) {
            return;
        }

        $day = Carbon::parse($date);

        $query->whereBetween($column, [$day->copy()->startOfDay(), $day->copy()->endOfDay()]);
    }

    /*
     * LABEL: Employee filters scope
     * Purpose: search / department_id / position_id sa employee na nasa
     * relation na $relation (hal. 'employee' o 'payroll.employee'). Isang
     * whereHas lang (isang EXISTS) kahit lahat ng tatlo ang gamit, at wala
     * talagang idinadagdag na query kapag wala sa tatlo ang may laman.
     *
     * @param array<string, mixed> $filters
     */
    public function scopeForEmployeeFilters(Builder $query, string $relation, array $filters): void
    {
        $search = $filters['search'] ?? null;
        $departmentId = $filters['department_id'] ?? null;
        $positionId = $filters['position_id'] ?? null;

        if (blank($search) && blank($departmentId) && blank($positionId)) {
            return;
        }

        $query->whereHas(
            $relation,
            fn (Builder $employee) => $employee
                ->when(filled($search), fn (Builder $employee) => $employee->matchingFirstOrLastName($search))
                ->when(filled($departmentId), fn (Builder $employee) => $employee->inDepartment($departmentId))
                ->when(filled($positionId), fn (Builder $employee) => $employee->inPosition($positionId)),
        );
    }
}