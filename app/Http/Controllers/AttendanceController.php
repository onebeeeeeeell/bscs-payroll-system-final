<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\AttendanceEmployeeRecordsRequest;
use App\Http\Requests\Attendance\AttendanceIndexRequest;
use App\Http\Requests\Attendance\AttendanceRequest;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
    ) {}

    public function index(AttendanceIndexRequest $request): Response
    {
        return Inertia::render('attendance/index', $this->attendanceService->indexData($request->filters()));
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

    // Archive and restore are used on the list and on the employee records page,
    // so they go back to whichever page (and filters) the request came from.
    public function destroy(Attendance $attendance): RedirectResponse
    {
        $this->attendanceService->delete($attendance);

        return back()->with('success', 'Attendance archived successfully.');
    }

    public function restore(int $attendanceId): RedirectResponse
    {
        $this->attendanceService->restoreById($attendanceId);

        return back()->with('success', 'Attendance restored successfully.');
    }
}