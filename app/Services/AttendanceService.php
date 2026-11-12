<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class AttendanceService
{
    /**
     * @param array{status?: 'active'|'deleted', department_id?: int|null, position_id?: int|null, date?: string|null} $filters
     */
    public function indexData(array $validated, array $filters): array
    {
        return [
            'attendances' => $this->paginate($validated),
            'stats' => $this->stats($validated),
            'departments' => $this->departmentOptions(),
            'positions' => $this->positionOptions(),
            'filters' => $filters,
        ];
    }

    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->with(['employee:id,first_name,last_name,suffix'])
            ->latest('work_date')
            ->latest('time_in')
            ->paginate($perPage)
            ->withQueryString(); // keep the filters when changing pages
    }

    public function departmentOptions(): Collection
    {
        return Department::query()
            ->orderBy('department_name')
            ->get(['id', 'department_name']);
    }

    public function positionOptions(): Collection
    {
        return Position::query()
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    public function activeEmployeesForSelection(): Collection
    {
        return Employee::query()
            ->whereNull('archived_at')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'suffix']);
    }

    /**
     * Numbers for the status cards above the table.
     * The first three follow the filters, "today" always counts today's active records.
     *
     * @return array{records: int, employees: int, with_overtime: int, today: int}
     */
    public function stats(array $filters = []): array
    {
        $summary = $this->filteredQuery($filters)
            ->selectRaw('COUNT(*) AS records')
            ->selectRaw('COUNT(DISTINCT employee_id) AS employees')
            ->selectRaw('COUNT(CASE WHEN overtime_hours > 0 THEN 1 END) AS with_overtime')
            ->first();

        return [
            'records' => (int) $summary->records,
            'employees' => (int) $summary->employees,
            'with_overtime' => (int) $summary->with_overtime,
            'today' => Attendance::query()
                ->whereHas('employee', fn ($employee) => $employee->whereNull('archived_at'))
                ->whereDate('work_date', today())
                ->count(),
        ];
    }

    public function create(array $data): Attendance
    {
        // transaction so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => Attendance::create($data)
        );
    }

    public function update(Attendance $attendance, array $data): Attendance
    {
        return DB::transaction(function () use ($attendance, $data) {
            $attendance->update($data);

            return $attendance;
        });
    }

    // soft delete: only fills deleted_at, the record is kept and can be restored
    public function delete(Attendance $attendance): void
    {
        DB::transaction(
            fn () => $attendance->delete()
        );
    }

    public function restore(Attendance $attendance): void
    {
        DB::transaction(
            fn () => $attendance->restore()
        );
    }

    /**
     * Shared by the table and the status cards so both always use the same filters.
     */
    private function filteredQuery(array $filters): Builder
    {
        $status = $filters['status'] ?? 'active';

        $query = Attendance::query()
            // "deleted" shows only the soft deleted records, anything else shows the active ones
            ->when($status === 'deleted', fn ($query) => $query->onlyTrashed());

        // Keep archived employees hidden from the normal active list, but allow deleted attendance
        // records to remain visible in the deleted filter even when the employee has been archived.
        if ($status !== 'deleted') {
            $query->whereHas('employee', fn ($query) => $query
                ->whereNull('archived_at')
                ->when($filters['department_id'] ?? null, fn ($employee, $departmentId) => $employee->where('department_id', $departmentId))
                ->when($filters['position_id'] ?? null, fn ($employee, $positionId) => $employee->where('position_id', $positionId)));
        } else {
            $query->whereHas('employee', fn ($query) => $query
                ->when($filters['department_id'] ?? null, fn ($employee, $departmentId) => $employee->where('department_id', $departmentId))
                ->when($filters['position_id'] ?? null, fn ($employee, $positionId) => $employee->where('position_id', $positionId)));
        }

        // one calendar date: show only the records of that day
        return $query->when($filters['date'] ?? null, fn ($query, $date) => $query->whereDate('work_date', $date));
    }

    // diri SAYLO ang halin sa controller tas callout nalang 
}