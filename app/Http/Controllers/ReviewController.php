<?php

namespace App\Http\Controllers;

use App\Http\Requests\Review\ReviewRequest;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function __construct(
        private ReviewService $reviewService,
    ) {}

    public function create(): Response
    {
        return Inertia::render('review/create');
    }

    public function store(ReviewRequest $request): RedirectResponse
    {
        $this->reviewService->submit($request->validated());

        return to_route('reviews.create')->with('success', 'Thank you! Your review will appear once it is approved.');
    }
}