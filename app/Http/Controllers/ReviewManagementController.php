<?php

namespace App\Http\Controllers;

use App\Http\Requests\Review\ReviewListRequest;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReviewManagementController extends Controller
{
    public function __construct(
        private ReviewService $reviewService,
    ) {}

    public function index(ReviewListRequest $request): Response
    {
        $filters = $request->filters();

        return Inertia::render('review/index', [
            'reviews' => $this->reviewService->paginate($filters['status']),
            'filters' => $filters,
        ]);
    }

    public function approve(Review $review): RedirectResponse
    {
        $this->reviewService->approve($review);

        return back()->with('success', 'Review approved. It is now on the welcome page.');
    }

    public function archive(Review $review): RedirectResponse
    {
        $this->reviewService->archive($review);

        return back()->with('success', 'Review archived.');
    }

    public function restore(Review $review): RedirectResponse
    {
        $this->reviewService->restore($review);

        return back()->with('success', 'Review restored.');
    }
}