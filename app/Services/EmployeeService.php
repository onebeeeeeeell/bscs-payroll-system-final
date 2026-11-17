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
use Illuminate\Support\Str;

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
        /*
         * LABEL: Hire date range query
         */
        $hireDates = Employee::query()
            ->hireDateBounds()
            ->first();

        /*
         * LABEL: Year range computation
         */
        $firstYear = $hireDates->first_hire_date
            ? Carbon::parse($hireDates->first_hire_date)->year
            : now()->year;
        $lastYear = $hireDates->last_hire_date
            ? max((int) now()->year, (int) Carbon::parse($hireDates->last_hire_date)->year)
            : now()->year;

        /*
         * LABEL: Returned payload
         */
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
        /*
         * LABEL: Employee list
         * Purpose: Nasa Employee model na ang logic (filters at eager load).
         * Dito, tinatawag na lang ang mga scopes.
         * - latest(): ORDER BY created_at DESC
         * - withQueryString(): ini-keep ang filters sa pagination links
         *   para hindi mag-reset pag nag-page 2.
         */
        return Employee::query()
            ->forFilters($filters)
            ->withListRelations()
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /*
     * LABEL: Department options
     * Purpose: id at department_name lang, alphabetical, para sa dropdown.
     */
    public function departmentOptions(): Collection
    {
        return Department::query()->alphabetical()->get(['id', 'department_name']);
    }

    /*
     * LABEL: Position options
     * Purpose: id at title lang, alphabetical, para sa dropdown.
     */
    public function positionOptions(): Collection
    {
        return Position::query()->alphabetical()->get(['id', 'title']);
    }

    /*
     * LABEL: Edit/create form options
     * Purpose: Isang bundle ng positions, departments, at employment statuses
     * para sa create/edit form.
     */
    public function employeeEditOptions(): array
    {
        return [
            'positions' => $this->positionOptions(),
            'departments' => $this->departmentOptions(),
            'statuses' => EmploymentStatus::query()->alphabetical()->get(['id', 'name']),
        ];
    }

    /*
     * LABEL: Create employee
     * Purpose: I-sanitize muna ang fields, tapos insert sa loob ng
     * transaction (atomic: kapag may pumalpak, rollback).
     */
    public function create(array $data): Employee
    {
        $fields = $this->sanitizeFields($data);


        return DB::transaction(
            fn () => Employee::create($fields)
        );
    }

    /*
     * LABEL: Update employee
     * Purpose: Same sa create: sanitize muna, tapos update sa loob ng
     * transaction. Ibinabalik ang updated na $employee.
     */
    public function update(Employee $employee, array $data): Employee
    {
        $fields = $this->sanitizeFields($data);


        return DB::transaction(function () use ($employee, $fields) {
            $employee->update($fields);
            return $employee;
        });
    }

    /**
     * Sanitize text fields and store blank optional values as null.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeFields(array $data): array
    {
        /*
         * LABEL: Text sanitization
         * Purpose: strip_tags() para tanggalin ang HTML tags, squish() para
         * i-trim at gawing isang space lang ang sunod-sunod na spaces,
         * toString() para bumalik sa plain string. String lang ang dadaan,
         * kaya hindi masisira ang null o numbers.
         */
        foreach (self::SANITIZED_TEXT_FIELDS as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = Str::of(strip_tags($data[$field]))
                    ->squish()
                    ->toString();
            }
        }

        /*
         * LABEL: Empty optional fields -> NULL
         * Purpose: NULL ang i-save at hindi ''. Mahalaga sa unique index ng
         * government IDs, kasi puwedeng maraming NULL pero isang '' lang.
         */
        foreach (['suffix', 'sss_no', 'philhealth_no', 'pagibig_no', 'tin_no'] as $field) {
            if (($data[$field] ?? null) === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }

    /*
     * LABEL: Archive employee
     * Purpose: Soft archive. Sine-set ang archived_at ng employee, tapos
     * sino-soft delete ang active attendance records niya sa isang bulk UPDATE.
     */
    public function archive(Employee $employee): void
    {
        DB::transaction(function () use ($employee) {
            $employee->update(['archived_at' => now()]);
            $employee->attendances()->delete();
        });
    }

    /*
     * LABEL: Restore employee
     * Purpose: Ibalik ang employee (archived_at = null) at i-restore
     * ang soft-deleted attendance records niya.
     */
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