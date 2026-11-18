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
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class AttendanceService
{
    use SanitizesText;

    private const STANDARD_WORK_HOURS = 8;

    /**
     * @param array{status?: 'active'|'deleted', department_id?: int|null, position_id?: int|null, date?: string|null} $filters
     */
    public function indexData(array $validated, array $filters): array
    {
        return [
            'attendances' => $this->paginate($validated),
            'stats' => $this->stats($validated),
            'departments' => Department::options(),
            'positions' => Position::options(),
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
        $data = $this->calculateWorkedHours($data);


        // transaction so nothing is saved if something fails in the middle
        $attendance = DB::transaction(
            fn () => Attendance::create($data)
        );

        NotificationService::flushMissingAttendanceCount();

        return $attendance;
    }

    public function punch(string $fullName, string $pin, string $action): Attendance
    {
        $fullName = $this->sanitizeFullName($fullName);

        return DB::transaction(function () use ($fullName, $pin, $action) {
            $employees = Employee::query()
                ->active()
                ->matchingFullName($fullName)
                ->lockForUpdate()
                ->get();

            if ($employees->isEmpty()) {
                throw ValidationException::withMessages([
                    'full_name' => 'No employee matches the entered name.',
                ]);
            }

            $matchingEmployees = $employees->filter(
                fn (Employee $employee) => $employee->pin && Hash::check($pin, $employee->pin),
            );

            if ($matchingEmployees->isEmpty()) {
                throw ValidationException::withMessages([
                    'pin' => 'The PIN is incorrect.',
                ]);
            }

            if ($matchingEmployees->count() > 1) {
                throw ValidationException::withMessages([
                    'pin' => 'The name and PIN do not uniquely identify one employee. Contact an administrator.',
                ]);
            }

            $employee = $matchingEmployees->first();

            $attendance = Attendance::withTrashed()
                ->where('employee_id', $employee->id)
                ->forDate(today()->toDateString())
                ->lockForUpdate()
                ->first();

            if ($action === 'time_in') {
                if ($attendance) {
                    throw ValidationException::withMessages([
                        'action' => 'A time-in has already been recorded for today.',
                    ]);
                }

                DB::afterCommit(fn () => NotificationService::flushMissingAttendanceCount());

                return Attendance::create([
                    'employee_id' => $employee->id,
                    'work_date' => today()->toDateString(),
                    'time_in' => now()->format('H:i:s'),
                    'time_out' => null,
                ]);
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
        $data = $this->calculateWorkedHours($data);


        $attendance = DB::transaction(function () use ($attendance, $data) {
            $attendance->update($data);


            return $attendance;
        });

        NotificationService::flushMissingAttendanceCount();

        return $attendance;
    }


    // soft delete: only fills deleted_at, the record is kept and can be restored
    public function delete(Attendance $attendance): void
    {
        DB::transaction(
            fn () => $attendance->delete()
        );

        NotificationService::flushMissingAttendanceCount();
    }


    public function restore(Attendance $attendance): void
    {
        DB::transaction(
            fn () => $attendance->restore()
        );

        NotificationService::flushMissingAttendanceCount();
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


    private function sanitizeFullName(string $fullName): string
    {
        $data = $this->sanitizeStringFields(
            ['full_name' => $fullName],
            ['full_name'],
        );

        return preg_replace('/\s+/u', ' ', $data['full_name']) ?? '';
    }
}