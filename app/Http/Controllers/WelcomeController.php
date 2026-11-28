<?php

namespace App\Http\Controllers;

use App\Services\ReviewService;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function __construct(
        private ReviewService $reviewService,
    ) {}

    public function __invoke(): Response
    {
        return Inertia::render('welcome', $this->reviewService->publicData());
    }
}