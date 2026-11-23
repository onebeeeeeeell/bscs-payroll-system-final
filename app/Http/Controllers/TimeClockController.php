<?php

namespace App\Http\Controllers;

use App\Http\Requests\TimeClockRequest;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
        $nameKey = hash('sha256', mb_strtolower(Str::squish($validated['full_name'])));
        $limiterKey = "time-clock:failed:{$request->ip()}:{$nameKey}";

        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            throw ValidationException::withMessages([
                'pin' => 'Too many unsuccessful attempts. Please wait one minute before trying again.',
            ]);
        }

        try {
            $this->attendanceService->punch(
                $validated['full_name'],
                $validated['pin'],
                $validated['action'],
            );
        } catch (ValidationException $exception) {
            if (isset($exception->errors()['full_name']) || isset($exception->errors()['pin'])) {
                RateLimiter::hit($limiterKey, 60);
            }

            throw $exception;
        }

        $message = $validated['action'] === 'time_in'
            ? 'Time-in recorded successfully.'
            : 'Time-out recorded successfully.';

        return to_route('time-clock.index')->with('success', $message);
    }
}
