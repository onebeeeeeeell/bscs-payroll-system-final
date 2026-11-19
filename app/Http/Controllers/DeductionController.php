<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListSearchRequest;
use App\Services\DeductionService;
use Inertia\Inertia;
use Inertia\Response;

class DeductionController extends Controller
{
    public function __construct(
        private DeductionService $deductionService,
    ) {}

    public function index(ListSearchRequest $request): Response
    {
        $search = $request->validated('search');

        return Inertia::render('deduction/index', [
            'deductions' => $this->deductionService->paginate($search),
            'filters' => ['search' => $search ?? ''],
        ]);
    }
}