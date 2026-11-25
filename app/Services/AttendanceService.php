<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Services\Concerns\SanitizesText;
use App\Support\YearBounds;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class AttendanceService
{
    use SanitizesText;

    private const STANDARD_WORK_HOURS = 8;

    // Each warning is shown under the field it is about: name -> full_name, PIN -> pin.
    private const UNKNOWN_NAME = 'No employee found with that name.';

    private const WRONG_PIN = 'Incorrect PIN.';

    private const DUPLICATE_DAY = 'This employee already has an attendance record for that date.';

    /**
     * One row per employee (the records of each employee are on the employee
     * records page). Filters are cleaned here, the queries live in the
     * Attendance scopes.
     *
     * @param array{status?: string|null, department_id?: int|string|null, position_id?: int|string|null, date?: string|null} $filters
     */
    public function indexData(array $filters): array
    {
        $filters = $this->sanitizeIndexFilters($filters);

        // stats() runs first: its "employees" is COUNT(DISTINCT employee_id) with the
        // same filters as the list, so the paginator reuses it instead of its own count.
        $stats = $this->stats($filters);

        return [
            'attendances' => $this->paginate($filters, total: $stats['employees']),
            'stats' => $stats,
            'departments' => Department::options(),
            'positions' => Position::options(),
            'filters' => $filters,
            'dateBounds' => $this->dateBounds(),
        ];
    }


    /*
     * LABEL: Attendance list
     * Purpose: Isang row bawat employee. Nasa Attendance model na ang lahat ng
     * logic (filters, grouping, eager load, sorting). Dito, tinatawag na lang
     * ang mga scopes.
     * withQueryString(): ini-keep ang filters sa pagination links.
     */
    public function paginate(array $filters = [], int $perPage = 10, ?int $total = null): LengthAwarePaginator
    {
        // $total: kung alam na ang bilang ng employees, hindi na mag-COUNT ang paginator.
        return Attendance::query()
            ->forFilters($filters)
            ->summaryPerEmployee()
            ->withEmployee()
            ->latestActivityFirst()
            ->paginate($perPage, total: $total)
            ->withQueryString();
    }


    public function activeEmployeesForSelection(): Collection
    {
        return Employee::query()
            ->forSelection()
            ->get();
    }

    /**
     * @return array{attendance: Attendance, employees: Collection}
     */
    public function editData(Attendance $attendance): array
    {
        return [
            'attendance' => $attendance->loadEditDetails(),
            'employees' => $this->activeEmployeesForSelection(),
        ];
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
            'today' => (int) $summary->today,
        ];
    }


    /**
     * Data for the employee records page (View in the attendance actions):
     * the employee's info, their attendance records, and the summary cards.
     * Filters are cleaned here, the queries live in the Attendance scopes.
     *
     * @param array{status?: string|null, date?: string|null} $filters
     */
    public function employeeRecordsData(int $employeeId, array $filters): array
    {
        $employee = Employee::query()
            ->withListRelations()
            ->findOrFail($employeeId);

        $filters = $this->sanitizeRecordFilters($filters);

        // one aggregate query: its "records" is also the total for the paginator
        $summary = Attendance::query()
            ->forEmployeeRecords($employee->id, $filters)
            ->hoursSummary()
            ->first();

        $records = (int) $summary->records;

        return [
            'employee' => $employee,
            'attendances' => Attendance::query()
                ->forEmployeeRecords($employee->id, $filters)
                ->latestFirst()
                ->paginate(10, total: $records)
                ->withQueryString(),
            'stats' => [
                'records' => $records,
                'total_hours' => (float) $summary->total_hours,
                'total_overtime' => (float) $summary->total_overtime,
                'open_records' => (int) $summary->open_records,
            ],
            'filters' => $filters,
            'dateBounds' => $this->dateBounds($employee),
        ];
    }


    public function create(array $data): Attendance
    {
        $data = Arr::only($data, ['employee_id', 'work_date', 'time_in', 'time_out']);
        $data = $this->calculateWorkedHours($data);

        try {
            return Attendance::create($data);
        } catch (UniqueConstraintViolationException) {
            // dalawang sabay na submit: ang database index ang nahuli (hindi ang validation)
            throw ValidationException::withMessages(['work_date' => self::DUPLICATE_DAY]);
        }
    }

    public function punch(string $fullName, string $pin, string $action): Attendance
    {
        $fullName = $this->sanitizeFullName($fullName);

        return DB::transaction(function () use ($fullName, $pin, $action) {
            $employees = Employee::query()
                ->forTimeClock($fullName)
                ->get();

            if ($employees->isEmpty()) {
                throw ValidationException::withMessages([
                    'full_name' => self::UNKNOWN_NAME,
                ]);
            }

            $matchingEmployees = $employees->filter(
                fn (Employee $employee) => $employee->pin && Hash::check($pin, $employee->pin),
            );

            if ($matchingEmployees->isEmpty()) {
                throw ValidationException::withMessages([
                    'pin' => self::WRONG_PIN,
                ]);
            }

            if ($matchingEmployees->count() > 1) {
                throw ValidationException::withMessages([
                    'full_name' => 'More than one employee has this name and PIN. Contact an administrator.',
                ]);
            }

            $employee = $matchingEmployees->first();

            $attendance = Attendance::query()
                ->forPunch($employee->id, today()->toDateString())
                ->first();

            if ($action === 'time_in') {
                if ($attendance) {
                    throw ValidationException::withMessages([
                        'action' => 'A time-in has already been recorded for today.',
                    ]);
                }

                try {
                    return Attendance::create([
                        'employee_id' => $employee->id,
                        'work_date' => today()->toDateString(),
                        'time_in' => now()->format('H:i:s'),
                        'time_out' => null,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    throw ValidationException::withMessages([
                        'action' => 'A time-in has already been recorded for today.',
                    ]);
                }
            }

            if (! $attendance || $attendance->trashed()) {
                throw ValidationException::withMessages([
                    'action' => 'Record your time-in before recording time-out.',
                ]);
            }

            if ($attendance->time_out !== null) {
                throw ValidationException::withMessages([
                    'action' => 'A time-out has already been recorded for today.',
                ]);
            }

            $timeOut = now()->format('H:i:s');
            $workedTime = $this->calculateWorkedHours([
                'time_in' => $attendance->time_in,
                'time_out' => $timeOut,
            ]);

            if ((float) $workedTime['hours_worked'] <= 0) {
                throw ValidationException::withMessages([
                    'action' => 'Time-out must be later than time-in.',
                ]);
            }

            $attendance->update([
                'time_out' => $timeOut,
                'hours_worked' => $workedTime['hours_worked'],
                'overtime_hours' => $workedTime['overtime_hours'],
            ]);

            return $attendance;
        });
    }


    public function update(Attendance $attendance, array $data): Attendance
    {
        $data = Arr::only($data, ['employee_id', 'work_date', 'time_in', 'time_out']);
        $data = $this->calculateWorkedHours($data);

        $attendance->update($data);

        return $attendance;
    }


    // soft delete: only fills deleted_at, the record is kept and can be restored
    public function delete(Attendance $attendance): void
    {
        $attendance->delete();
    }


    public function restore(Attendance $attendance): void
    {
        $attendance->restore();
    }

    public function restoreById(int $attendanceId): void
    {
        $attendance = Attendance::withTrashed()->findOrFail($attendanceId);

        // Kapag may active na record na ang employee sa araw na 'yon, hindi pwedeng i-restore
        // (magiging dalawa ang record at madodoble ang paid days).
        $hasActiveRecord = Attendance::query()
            ->forEmployee($attendance->employee_id)
            ->forDate($attendance->work_date)
            ->whereKeyNot($attendance->getKey())
            ->exists();

        if ($hasActiveRecord) {
            throw ValidationException::withMessages(['attendance' => self::DUPLICATE_DAY]);
        }

        $this->restore($attendance);
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


    /*
     * Year range of the date filter: current year, plus any year that has an
     * attendance record or a hire date. For one employee, only their own records
     * and hire date count.
     *
     * @return array{min: string, max: string}
     */
    private function dateBounds(?Employee $employee = null): array
    {
        $workDates = Attendance::query()
            ->when($employee, fn ($query) => $query->forEmployee($employee->id))
            ->workDateBounds()
            ->first();

        $hireDates = $employee
            ? (object) ['first_hire_date' => $employee->date_hired, 'last_hire_date' => $employee->date_hired]
            : Employee::query()->hireDateBounds()->first();

        return YearBounds::covering([
            $workDates->first_date,
            $workDates->last_date,
            $hireDates->first_hire_date,
            $hireDates->last_hire_date,
        ]);
    }


    // department and position can only be a positive whole number (else blank)
    private function sanitizeIndexFilters(array $filters): array
    {
        return [
            'department_id' => $this->sanitizeId($filters['department_id'] ?? null),
            'position_id' => $this->sanitizeId($filters['position_id'] ?? null),
            ...$this->sanitizeRecordFilters($filters),
        ];
    }


    // status can only be active or deleted, date must be a real Y-m-d (else blank)
    private function sanitizeRecordFilters(array $filters): array
    {
        return [
            'status' => ($filters['status'] ?? 'active') === 'deleted' ? 'deleted' : 'active',
            'date' => $this->sanitizeDate($filters['date'] ?? null),
        ];
    }


    private function sanitizeId(int|string|null $id): string
    {
        $id = filter_var($id, FILTER_VALIDATE_INT);

        return $id !== false && $id > 0 ? (string) $id : '';
    }


    private function sanitizeFullName(string $fullName): string
    {
        // strip tags + squish na ang ginagawa ng trait, kaya hindi na kailangan ng extra preg_replace
        return $this->sanitizeStringFields(
            ['full_name' => $fullName],
            ['full_name'],
        )['full_name'];
    }
}