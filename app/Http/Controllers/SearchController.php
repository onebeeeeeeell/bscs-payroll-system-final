<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __construct(
        private SearchService $searchService,
    ) {}

    public function __invoke(SearchRequest $request): JsonResponse
    {
        return response()->json([
            'employees' => $this->searchService->employees($request->validated('q') ?? ''),
        ]);
    }
}