<?php

namespace App\Http\Controllers;

use App\Http\Requests\TimeClockRequest;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TimeClockController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('time-clock');
    }

    public function store(TimeClockRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->attendanceService->punch(
            $validated['full_name'],
            $validated['pin'],
            $validated['action'],
        );

        $message = $validated['action'] === 'time_in'
            ? 'Time-in recorded successfully.'
            : 'Time-out recorded successfully.';

        return to_route('time-clock.index')->with('success', $message);
    }
}
