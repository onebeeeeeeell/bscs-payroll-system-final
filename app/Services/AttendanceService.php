<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Services\Concerns\SanitizesText;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;


class AttendanceService
{
    use SanitizesText;

    private const SANITIZED_FIELDS = [
        'employee_id',
        'work_date',
        'time_in',
        'time_out',
    ];

    private const STANDARD_WORK_HOURS = 8;

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


    /*
     * LABEL: Attendance list
     * Purpose: Nasa Attendance model na ang lahat ng logic (filters, eager load,
     * sorting). Dito, tinatawag na lang ang mga scopes.
     * withQueryString(): ini-keep ang filters sa pagination links.
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return Attendance::query()
            ->forFilters($filters)
            ->withEmployee()
            ->latestFirst()
            ->paginate($perPage)
            ->withQueryString();
    }


    public function departmentOptions(): Collection
    {
        return Department::query()
            ->alphabetical()
            ->get(['id', 'department_name']);
    }


    public function positionOptions(): Collection
    {
        return Position::query()
            ->alphabetical()
            ->get(['id', 'title']);
    }


    public function activeEmployeesForSelection(): Collection
    {
        return Employee::query()
            ->forSelection()
            ->get();
    }


    /**
     * Numbers for the status cards above the table.
     * The first three follow the filters, "today" always counts today's active records.
     *
     * @return array{records: int, employees: int, with_overtime: int, today: int}
     */
    public function stats(array $filters = []): array
    {
        $summary = Attendance::query()
            ->forFilters($filters)
            ->summary()
            ->first();


        return [
            'records' => (int) $summary->records,
            'employees' => (int) $summary->employees,
            'with_overtime' => (int) $summary->with_overtime,
            'today' => Attendance::query()
                ->forActiveEmployees()
                ->today()
                ->count(),
        ];
    }


    public function create(array $data): Attendance
    {
        $data = $this->sanitizeFields($data);
        $data = $this->calculateWorkedHours($data);


        // transaction so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => Attendance::create($data)
        );
    }


    public function update(Attendance $attendance, array $data): Attendance
    {
        $data = $this->sanitizeFields($data);
        $data = $this->calculateWorkedHours($data);


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


    private function calculateWorkedHours(array $data): array
    {
        if (! isset($data['time_in'], $data['time_out'])) {
            return $data;
        }


        $timeIn = Carbon::parse($data['time_in']);
        $timeOut = Carbon::parse($data['time_out']);


        $hoursWorked = round($timeIn->diffInMinutes($timeOut, false) / 60, 2);
        $overtimeHours = max(0, round($hoursWorked - self::STANDARD_WORK_HOURS, 2));


        $data['hours_worked'] = number_format($hoursWorked, 2, '.', '');
        $data['overtime_hours'] = number_format($overtimeHours, 2, '.', '');


        return $data;
    }


    /**
     * Sanitize the text-like fields. No whitelist here: #[Fillable] on the
     * model and $request->validated() already limit what can be saved.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeFields(array $data): array
    {
        return $this->sanitizeStringFields($data, self::SANITIZED_FIELDS, false);
    }
}