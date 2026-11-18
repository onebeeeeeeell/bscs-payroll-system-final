<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
    'employee_id',
    'work_date',
    'time_in',
    'time_out',
    'hours_worked',
    'overtime_hours'
])]

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    /*
     * LABEL: Date scope
     * Purpose: I-filter by work_date. Tinatawag bilang ->forDate($date).
     */
    public function scopeForDate(Builder $query, string $date): void
    {
        $query->where('work_date', $date);
    }

    /*
     * LABEL: Today scope
     * Purpose: Shortcut ng forDate() para sa araw ngayon, kaya hindi na
     * kailangang mag-today()->toDateString() sa service.
     */
    public function scopeToday(Builder $query): void
    {
        $query->forDate(today()->toDateString());
    }

    /*
     * LABEL: Active employees scope
     * Purpose: Attendance records lang ng mga employee na hindi pa naka-archive
     * (gamit ang active() scope ng Employee).
     */
    public function scopeForActiveEmployees(Builder $query): void
    {
        $query->whereHas('employee', fn (Builder $employee) => $employee->active());
    }

    /*
     * LABEL: Eager load employee
     * Purpose: with() para i-load na agad ang employee (iwas N+1 problem).
     * ':id,first_name,...' = piling columns lang para magaan ang query.
     */
    public function scopeWithEmployee(Builder $query): void
    {
        $query->with(['employee:id,first_name,last_name,suffix']);
    }

    /*
     * LABEL: Latest first scope
     * Purpose: Sorting ng attendance list: pinakabagong work_date muna,
     * tapos pinakabagong time_in kung pareho ang date.
     */
    public function scopeLatestFirst(Builder $query): void
    {
        $query->latest('work_date')->latest('time_in');
    }

    /*
     * LABEL: Summary scope
     * Purpose: Aggregates para sa status cards, ISANG query lang:
     * records (total), employees (distinct), with_overtime (may OT).
     */
    public function scopeSummary(Builder $query): void
    {
        $query
            ->selectRaw('COUNT(*) AS records')
            ->selectRaw('COUNT(DISTINCT employee_id) AS employees')
            ->selectRaw('COUNT(CASE WHEN overtime_hours > 0 THEN 1 END) AS with_overtime');
    }

    /*
     * LABEL: Filters scope
     * Purpose: Lahat ng index filters sa iisang scope.
     * - status 'deleted': onlyTrashed() (soft-deleted records lang).
     * - status 'active' (default): active employees lang ang kasama.
     * - department / position: dumadaan sa employee relation gamit ang
     *   inDepartment() at inPosition() scopes ng Employee.
     * - date: forDate() kung may laman.
     *
     * @param array{status?: 'active'|'deleted', department_id?: int|null, position_id?: int|null, date?: string|null} $filters
     */
    public function scopeForFilters(Builder $query, array $filters): void
    {
        $status = $filters['status'] ?? 'active';

        $query
            ->when($status === 'deleted', fn (Builder $query) => $query->onlyTrashed())
            ->whereHas('employee', fn (Builder $employee) => $employee
                ->when($status !== 'deleted', fn (Builder $employee) => $employee->active())
                ->when($filters['department_id'] ?? null, fn (Builder $employee, $departmentId) => $employee->inDepartment($departmentId))
                ->when($filters['position_id'] ?? null, fn (Builder $employee, $positionId) => $employee->inPosition($positionId)))
            ->when($filters['date'] ?? null, fn (Builder $query, $date) => $query->forDate($date));
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}