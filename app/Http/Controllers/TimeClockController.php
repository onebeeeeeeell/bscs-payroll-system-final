<?php

namespace App\Http\Controllers;

use App\Http\Requests\TimeClockRequest;
use App\Services\AttendanceService;
use App\Services\TimeClockThrottle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TimeClockController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
        private TimeClockThrottle $throttle,
    ) {}

    public function index(): Response
    {
        return Inertia::render('time-clock');
    }

    public function store(TimeClockRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->throttle->ensureAllowed($request->ip(), $validated['full_name']);

        try {
            $this->attendanceService->punch(
                $validated['full_name'],
                $validated['pin'],
                $validated['action'],
            );
        } catch (ValidationException $exception) {
            if (isset($exception->errors()['full_name']) || isset($exception->errors()['pin'])) {
                $this->throttle->recordFailure($request->ip(), $validated['full_name']);
            }

            throw $exception;
        }

        $message = $validated['action'] === 'time_in'
            ? 'Time-in recorded successfully.'
            : 'Time-out recorded successfully.';

        return to_route('time-clock.index')->with('success', $message);
    }
}