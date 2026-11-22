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
     * LABEL: Employee scope
     * Purpose: I-filter by employee_id. Tinatawag bilang ->forEmployee($id).
     */
    public function scopeForEmployee(Builder $query, int $employeeId): void
    {
        $query->where('employee_id', $employeeId);
    }

    /*
     * LABEL: Date range scope
     * Purpose: I-filter by work_date na nasa pagitan ng start at end (kasama
     * ang dalawa). Tinatawag bilang ->betweenDates($start, $end). Kaya nitong
     * gumamit ng (employee_id, work_date) index.
     */
    public function scopeBetweenDates(Builder $query, string $startDate, string $endDate): void
    {
        $query->whereBetween('work_date', [$startDate, $endDate]);
    }

    /*
     * LABEL: Totals per employee scope
     * Purpose: Aggregates para sa payroll, ISANG query lang para sa lahat ng
     * employee (GROUP BY employee_id):
     * - paid_days: sum ng hours_worked (hanggang $standardHours lang bawat araw)
     *   hinati sa $standardHours, kaya may half-day (hal. 10.5). CASE ang gamit
     *   (hindi LEAST) para gumana sa SQLite at sa production DB; ang * 1.0 ay
     *   para hindi mag-integer division sa SQLite.
     * - overtime_hours: kabuuang OT hours.
     * - open_records: bilang ng records na wala pang time_out.
     * Soft-deleted records ay hindi kasama (SoftDeletes na ang bahala).
     */
    public function scopeTotalsPerEmployee(Builder $query, int $standardHours = 8): void
    {
        $query
            ->select('employee_id')
            ->selectRaw(
                'ROUND(COALESCE(SUM(CASE WHEN hours_worked > ? THEN ? ELSE hours_worked END), 0) * 1.0 / ?, 2) AS paid_days',
                [$standardHours, $standardHours, $standardHours],
            )
            ->selectRaw('COALESCE(SUM(overtime_hours), 0) AS overtime_hours')
            ->selectRaw('COUNT(CASE WHEN time_out IS NULL THEN 1 END) AS open_records')
            ->groupBy('employee_id');
    }

    /*
     * LABEL: Status scope
     * Purpose: 'deleted' = soft-deleted records lang (onlyTrashed). Kahit ano
     * pa ang ibang value, active records ang default. Ginagamit ng forFilters()
     * at ng employee records page, kaya hindi na inuulit ang logic.
     */
    public function scopeInStatus(Builder $query, string $status): void
    {
        $query->when($status === 'deleted', fn (Builder $query) => $query->onlyTrashed());
    }

    /*
     * LABEL: Hours summary scope
     * Purpose: Aggregates para sa cards ng isang employee, ISANG query lang:
     * records (total), total_hours, total_overtime, open_records (wala pang
     * time_out). COALESCE para 0 ang lumabas, hindi null, kapag walang records.
     */
    public function scopeHoursSummary(Builder $query): void
    {
        $query
            ->selectRaw('COUNT(*) AS records')
            ->selectRaw('COALESCE(SUM(hours_worked), 0) AS total_hours')
            ->selectRaw('COALESCE(SUM(overtime_hours), 0) AS total_overtime')
            ->selectRaw('COUNT(CASE WHEN time_out IS NULL THEN 1 END) AS open_records');
    }

    /*
     * LABEL: Employee records filters scope
     * Purpose: Filters ng employee records page: status (active/deleted) at
     * date. Walang department/position dito kasi isang employee lang ang
     * tinitingnan. Tinatawag bilang ->forEmployeeRecords($id, $filters).
     *
     * @param array{status?: 'active'|'deleted', date?: string|null} $filters
     */
    public function scopeForEmployeeRecords(Builder $query, int $employeeId, array $filters): void
    {
        $query
            ->forEmployee($employeeId)
            ->inStatus($filters['status'] ?? 'active')
            ->when($filters['date'] ?? null, fn (Builder $query, $date) => $query->forDate($date));
    }

    /*
     * LABEL: Filters scope
     * Purpose: Lahat ng index filters sa iisang scope.
     * - status 'deleted': inStatus() -> soft-deleted records lang.
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
            ->inStatus($status)
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