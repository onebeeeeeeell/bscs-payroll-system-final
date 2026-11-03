<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class EmployeeService
{
    /**
     * @param array{department_id?: int|null, position_id?: int|null, hire_year?: int|null, hire_month?: int|null} $filters
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return Employee::query()
            ->with(['position:id,title', 'department:id,department_name'])
            ->when($filters['department_id'] ?? null, fn ($query, $departmentId) => $query->where('department_id', $departmentId))
            ->when($filters['position_id'] ?? null, fn ($query, $positionId) => $query->where('position_id', $positionId))
            ->when($filters['hire_year'] ?? null, fn ($query, $year) => $query->whereYear('date_hired', $year))
            ->when($filters['hire_month'] ?? null, fn ($query, $month) => $query->whereMonth('date_hired', $month))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Employee
    {
        // transaction so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => Employee::create($data)
        );
    }

    public function update(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data) {
            $employee->update($data);

            return $employee;
        });
    }

    public function delete(Employee $employee): void
    {
        DB::transaction(
            fn () => $employee->delete()
        );
    }
}