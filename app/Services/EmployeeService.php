<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Position;
use App\Services\Concerns\SanitizesText;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    use SanitizesText;

    // Ito lang ang fields na puwedeng pumasok galing sa labas. Hindi kasama ang
    // archived_at: archive() at restore() lang ang puwedeng mag-set nito.
    private const EDITABLE_FIELDS = [
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
        'pin',
    ];

    // Free text lang ang nililinis dito. Ang status at government IDs ay
    // validated na sa EmployeeRequest (exists / digits), kaya wala nang gagawin.
    private const SANITIZED_TEXT_FIELDS = [
        'first_name',
        'last_name',
        'suffix',
    ];

    /**
     * @param array{department_id?: int|null, position_id?: int|null, hire_year?: int|null, hire_month?: int|null, status?: 'active'|'archived'} $filters
     */
    public function indexData(array $filters): array
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
            'employees' => $this->paginate($filters),
            'departments' => Department::options(),
            'positions' => Position::options(),
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
         * - latest()->latest('id'): ORDER BY created_at DESC, with id as a tiebreaker
         * - withQueryString(): ini-keep ang filters sa pagination links
         *   para hindi mag-reset pag nag-page 2.
         */
        return Employee::query()
            ->forFilters($filters)
            ->withListRelations()
            ->latest()
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /*
     * LABEL: Edit/create form options
     * Purpose: Isang bundle ng positions, departments, at employment statuses
     * para sa create/edit form.
     */
    public function employeeEditOptions(): array
    {
        return [
            'positions' => Position::options(),
            'departments' => Department::options(),
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

        // LABEL: Transaction; puwedeng tanggalin kung walang idadagdag na related writes sa susunod.
        $employee = DB::transaction(
            fn () => Employee::create($fields)
        );

        NotificationService::flushMissingAttendanceCount();

        return $employee;
    }

    /*
     * LABEL: Update employee
     * Purpose: Same sa create: sanitize muna, tapos update sa loob ng
     * transaction. Ibinabalik ang updated na $employee.
     */
    public function update(Employee $employee, array $data): Employee
    {
        $fields = $this->sanitizeFields($data);

        // LABEL: Transaction; puwedeng tanggalin kung walang idadagdag na related writes sa susunod.
        return DB::transaction(function () use ($employee, $fields) {
            $employee->update($fields);
            return $employee;
        });
    }

    // LABEL: Main sanitization point; dito nililinis ang fields bago i-save.
    /**
     * Sanitize text fields and store blank optional values as null.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeFields(array $data): array
    {
        // Itapon muna ang fields na hindi dapat galing sa labas (hal. archived_at).
        $data = Arr::only($data, self::EDITABLE_FIELDS);

        /*
         * LABEL: Text sanitization
         * Purpose: Iisang sanitizer na lang (SanitizesText trait): strip_tags
         * tapos squish. String lang ang dadaan, kaya hindi masisira ang null
         * o numbers. Names at suffix lang ang kasama.
         */
        $data = $this->sanitizeStringFields($data, self::SANITIZED_TEXT_FIELDS);

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

        // Blank PIN sa edit = huwag galawin ang existing PIN.
        // Ang hashing ay nasa Employee model na ('pin' => 'hashed' cast).
        if (empty($data['pin'])) {
            unset($data['pin']);
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

        NotificationService::flushMissingAttendanceCount();
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

        NotificationService::flushMissingAttendanceCount();
    }

}