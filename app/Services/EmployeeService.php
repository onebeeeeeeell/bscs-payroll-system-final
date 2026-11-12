<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Position;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    /**
     * @param array{department_id?: int|null, position_id?: int|null, hire_year?: int|null, hire_month?: int|null, status?: 'active'|'archived'} $filters
     */
    public function indexData(array $validated, array $filters): array
    {
        $firstHireDate = Employee::query()->min('date_hired');
        $lastHireDate = Employee::query()->max('date_hired');
        $firstYear = $firstHireDate ? Carbon::parse($firstHireDate)->year : now()->year;
        $lastYear = $lastHireDate ? max((int) now()->year, (int) Carbon::parse($lastHireDate)->year) : now()->year;

        return [
            'employees' => $this->paginate($validated),
            'departments' => $this->departmentOptions(),
            'positions' => $this->positionOptions(),
            'hireYears' => range($lastYear, $firstYear),
            'filters' => $filters,
        ];
    }

    /**
     * @param array{department_id?: int|null, position_id?: int|null, hire_year?: int|null, hire_month?: int|null, status?: 'active'|'archived'} $filters
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return Employee::query()
            ->with(['position:id,title', 'department:id,department_name'])
            ->when(
                ($filters['status'] ?? 'active') === 'archived',
                fn ($query) => $query->whereNotNull('archived_at'),
                fn ($query) => $query->whereNull('archived_at'),
            )
            ->when($filters['department_id'] ?? null, fn ($query, $departmentId) => $query->where('department_id', $departmentId))
            ->when($filters['position_id'] ?? null, fn ($query, $positionId) => $query->where('position_id', $positionId))
            ->when($filters['hire_year'] ?? null, fn ($query, $year) => $query->whereYear('date_hired', $year))
            ->when($filters['hire_month'] ?? null, fn ($query, $month) => $query->whereMonth('date_hired', $month))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function departmentOptions(): Collection
    {
        return Department::query()->orderBy('department_name')->get(['id', 'department_name']);
    }

    public function positionOptions(): Collection
    {
        return Position::query()->orderBy('title')->get(['id', 'title']);
    }

    public function employeeEditOptions(): array
    {
        return [
            'positions' => $this->positionOptions(),
            'departments' => $this->departmentOptions(),
            'statuses' => EmploymentStatus::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function create(array $data): Employee
    {
        $sanitizedData = $this->sanitizeEmployeeData($data);

        return DB::transaction(
            fn () => Employee::create($sanitizedData)
        );
    }

    public function update(Employee $employee, array $data): Employee
    {
        $sanitizedData = $this->sanitizeEmployeeData($data);

        return DB::transaction(function () use ($employee, $sanitizedData) {
            $employee->update($sanitizedData);
            return $employee;
        });
    }

    /**
     * Normalize validated employee input and keep only editable employee fields.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeEmployeeData(array $data): array
    {
        $allowedFields = [
            'first_name',
            'last_name',
            'suffix',
            'position_id',
            'department_id',
            'date_hired',
            'daily_rate',
            'sss_no',
            'philhealth_no',
            'pagibig_no',
            'tin_no',
            'status',
        ];
        $sanitizedData = array_intersect_key($data, array_flip($allowedFields));

        foreach (['first_name', 'last_name', 'suffix'] as $field) {
            if (isset($sanitizedData[$field]) && is_string($sanitizedData[$field])) {
                $sanitizedData[$field] = trim(preg_replace('/\s+/u', ' ', trim($sanitizedData[$field])) ?? '');
            }
        }

        foreach (['suffix', 'sss_no', 'philhealth_no', 'pagibig_no', 'tin_no'] as $field) {
            if (isset($sanitizedData[$field]) && is_string($sanitizedData[$field])) {
                $sanitizedData[$field] = trim($sanitizedData[$field]);
                if ($sanitizedData[$field] === '') {
                    $sanitizedData[$field] = null;
                }
            }
        }

        return $sanitizedData;
    }

    public function archive(Employee $employee): void
    {
        DB::transaction(function () use ($employee) {
            $employee->update(['archived_at' => now()]);
            $employee->attendances()
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now()]);
        });
    }

    public function restore(Employee $employee): void
    {
        DB::transaction(function () use ($employee) {
            $employee->update(['archived_at' => null]);
            $employee->attendances()
                ->onlyTrashed()
                ->restore();
        });
    }

}