<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Position;
use App\Models\Department;
use App\Models\EmploymentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EmployeeService
{
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
        });
    }

    public function restore(Employee $employee): void
    {
        DB::transaction(function () use ($employee) {
            $employee->update(['archived_at' => null]);
        });
    }

    // diri saylo ang sa controller part na method
    public function kuhaSangPosition(): Collection
    {
       return Position::query()->orderBy('title')->get(['id', 'title']);
    }

    public function kuhaSangDepartment(): Collection
    {
       return Department::query()->orderBy('department_name')->get(['id', 'department_name']);
    }
    public function kuhaSangStatus(): Collection
    {
       return EmploymentStatus::query()->orderBy('name')->get(['id', 'name']);
    }

}