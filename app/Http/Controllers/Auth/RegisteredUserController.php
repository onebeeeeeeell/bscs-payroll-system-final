<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(
        private UserService $userService,
    ) {}

    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        Auth::login($this->userService->register($request->validated()));

        // bagong session id pagkatapos mag-login (proteksyon sa session fixation)
        $request->session()->regenerate();

        return to_route('dashboard');
    }
}