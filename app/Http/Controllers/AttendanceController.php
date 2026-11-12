<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\AttendanceIndexRequest;
use App\Http\Requests\Attendance\AttendanceRequest;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
    ) {}

    public function index(AttendanceIndexRequest $request): Response
    {
        $validated = $request->validated();
        $filters = $request->filters();

        return Inertia::render('attendance/index', $this->attendanceService->indexData($validated, $filters));
    }

    public function create(): Response
    {
        return Inertia::render('attendance/create', [
            'employees' => $this->attendanceService->activeEmployeesForSelection(),
        ]);
    }

    public function store(AttendanceRequest $request): RedirectResponse
    {
        $this->attendanceService->create($request->validated());

        return to_route('attendance.index')->with('success', 'Attendance recorded successfully.');
    }

    public function show(Attendance $attendance): Response
    {
        $attendance->load('employee');

        return Inertia::render('attendance/show', [
            'attendance' => $attendance,
        ]);
    }

    public function edit(Attendance $attendance): Response
    {
        $attendance->load('employee');

        return Inertia::render('attendance/edit', [
            'attendance' => $attendance,
            'employees' => $this->attendanceService->activeEmployeesForSelection(),
        ]);
    }

    public function update(AttendanceRequest $request, Attendance $attendance): RedirectResponse
    {
        $this->attendanceService->update($attendance, $request->validated());

        return to_route('attendance.index')->with('success', 'Attendance updated successfully.');
    }

    public function destroy(Request $request, Attendance $attendance): RedirectResponse
    {
        $this->attendanceService->delete($attendance);

        return to_route('attendance.index', $request->only([
            'status',
            'department_id',
            'position_id',
            'date',
        ]))->with('success', 'Attendance deleted successfully.');
    }

    public function restore(Request $request, Attendance $attendance): RedirectResponse
    {
        $this->attendanceService->restore($attendance);

        return to_route('attendance.index', $request->only([
            'status',
            'department_id',
            'position_id',
            'date',
        ]))->with('success', 'Attendance restored successfully.');
    }
}