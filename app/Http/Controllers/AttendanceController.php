<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\AttendanceEmployeeRecordsRequest;
use App\Http\Requests\Attendance\AttendanceIndexRequest;
use App\Http\Requests\Attendance\AttendanceRequest;
use App\Models\Attendance;
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

    // View in the attendance actions: all attendance records of one employee
    public function employeeRecords(AttendanceEmployeeRecordsRequest $request, int $employeeId): Response
    {
        return Inertia::render('attendance/employee-records', $this->attendanceService->employeeRecordsData(
            $employeeId,
            $request->validated(),
        ));
    }

    public function edit(Attendance $attendance): Response
    {
        return Inertia::render('attendance/edit', $this->attendanceService->editData($attendance));
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

    public function restore(Request $request, int $attendanceId): RedirectResponse
    {
        $this->attendanceService->restoreById($attendanceId);

        return to_route('attendance.index', $request->only([
            'status',
            'department_id',
            'position_id',
            'date',
        ]))->with('success', 'Attendance restored successfully.');
    }
}