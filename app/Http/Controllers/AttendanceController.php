<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Attendance\AttendanceRequest;
use Inertia\Response;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $attendances = Attendance::query()
            ->with('employee:id,first_name,last_name,suffix')
            ->whereHas('employee', fn ($query) => $query->whereNull('archived_at'))
            ->latest('work_date')
            ->latest('time_in')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('attendance/index', [
            'attendances' => $attendances,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('attendance/create', [
            'employees' => Employee::query()
                ->whereNull('archived_at')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'suffix']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AttendanceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        Attendance::create([...$data, ...$this->calculateHours($data['time_in'], $data['time_out'])]);

        return to_route('attendance.index')->with('success', 'Attendance record created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Attendance $attendance)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Attendance $attendance): Response
    {
        return Inertia::render('attendance/edit', [
            'attendance' => $attendance->only([
                'id',
                'employee_id',
                'work_date',
                'time_in',
                'time_out',
                'hours_worked',
                'overtime_hours',
            ]),
            'employees' => Employee::query()
                ->whereNull('archived_at')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'suffix']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AttendanceRequest $request, Attendance $attendance): RedirectResponse
    {
        $data = $request->validated();
        $attendance->update([...$data, ...$this->calculateHours($data['time_in'], $data['time_out'])]);

        return to_route('attendance.index')->with('success', 'Attendance record updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Attendance $attendance)
    {
        //
    }

    /**
     * @return array{hours_worked: string, overtime_hours: string}
     */
    private function calculateHours(string $timeIn, string $timeOut): array
    {
        [$inHours, $inMinutes] = array_map('intval', explode(':', $timeIn));
        [$outHours, $outMinutes] = array_map('intval', explode(':', $timeOut));
        $workedMinutes = ($outHours * 60 + $outMinutes) - ($inHours * 60 + $inMinutes);
        $overtimeMinutes = max(0, $workedMinutes - 8 * 60);

        return [
            'hours_worked' => number_format($workedMinutes / 60, 2, '.', ''),
            'overtime_hours' => number_format($overtimeMinutes / 60, 2, '.', ''),
        ];
    }
}