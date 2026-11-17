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
    private const SANITIZED_TEXT_FIELDS = [
        'first_name',
        'last_name',
        'suffix',
        'sss_no',
        'philhealth_no',
        'pagibig_no',
        'tin_no',
        'status',
    ];

    /**
     * @param array{department_id?: int|null, position_id?: int|null, hire_year?: int|null, hire_month?: int|null, status?: 'active'|'archived'} $filters
     */
    public function indexData(array $validated, array $filters): array
    {
        $hireDates = Employee::query()
            ->selectRaw('MIN(date_hired) AS first_hire_date, MAX(date_hired) AS last_hire_date')
            ->first();
        $firstYear = $hireDates->first_hire_date
            ? Carbon::parse($hireDates->first_hire_date)->year
            : now()->year;
        $lastYear = $hireDates->last_hire_date
            ? max((int) now()->year, (int) Carbon::parse($hireDates->last_hire_date)->year)
            : now()->year;

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
        $query = Employee::query()
            ->with(['position:id,title', 'department:id,department_name'])
            ->when(
                ($filters['status'] ?? 'active') === 'archived',
                fn ($query) => $query->whereNotNull('archived_at'),
                fn ($query) => $query->whereNull('archived_at'),
            )
            ->when($filters['department_id'] ?? null, fn ($query, $departmentId) => $query->where('department_id', $departmentId))
            ->when($filters['position_id'] ?? null, fn ($query, $positionId) => $query->where('position_id', $positionId));

        $hireYear = $filters['hire_year'] ?? null;
        $hireMonth = $filters['hire_month'] ?? null;

        if ($hireYear !== null) {
            $startDate = Carbon::create((int) $hireYear, (int) ($hireMonth ?? 1), 1);
            $endDate = $hireMonth !== null
                ? $startDate->copy()->endOfMonth()
                : $startDate->copy()->endOfYear();

            $query->whereBetween('date_hired', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ]);
        } elseif ($hireMonth !== null) {
            $query->whereMonth('date_hired', $hireMonth);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
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
        $sanitizedData = $this->sanitizeTextFields($sanitizedData);

        foreach (['first_name', 'last_name', 'suffix'] as $field) {
            if (isset($sanitizedData[$field]) && is_string($sanitizedData[$field])) {
                $normalized = preg_replace('/\s+/u', ' ', $sanitizedData[$field]);
                if ($normalized === null) {
                    throw new \InvalidArgumentException("The employee {$field} contains invalid text encoding.");
                }

                $sanitizedData[$field] = trim($normalized);
            }
        }

        if (($sanitizedData['suffix'] ?? null) === '') {
            $sanitizedData['suffix'] = null;
        }

        foreach (['sss_no', 'philhealth_no', 'pagibig_no', 'tin_no'] as $field) {
            if (($sanitizedData[$field] ?? null) === '') {
                $sanitizedData[$field] = null;
            }
        }

        return $sanitizedData;
    }

    private function sanitizeTextFields(array $data): array
    {
        foreach (self::SANITIZED_TEXT_FIELDS as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim(strip_tags($data[$field]));
            }
        }

        return $data;
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