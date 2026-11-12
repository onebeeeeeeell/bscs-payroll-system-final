<?php

namespace App\Http\Controllers;

use App\Http\Requests\Deduction\DeductionRequest;
use App\Models\Deduction;
use App\Services\DeductionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeductionController extends Controller
{
    public function __construct(
        private DeductionService $deductionService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('deduction/index', [
            'deductions' => $this->deductionService->paginate(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('deduction/create');
    }

    public function store(DeductionRequest $request): RedirectResponse
    {
        $this->deductionService->create($request->validated());

        return to_route('deductions.index')->with('success', 'Deduction created successfully.');
    }

    public function edit(Deduction $deduction): Response
    {
        return Inertia::render('deduction/edit', [
            'deduction' => $deduction->load(['payroll', 'contributionRate']),
        ]);
    }

    public function update(DeductionRequest $request, Deduction $deduction): RedirectResponse
    {
        $this->deductionService->update($deduction, $request->validated());

        return to_route('deductions.index')->with('success', 'Deduction updated successfully.');
    }

    public function destroy(Deduction $deduction): RedirectResponse
    {
        $this->deductionService->delete($deduction);

        return to_route('deductions.index')->with('success', 'Deduction deleted successfully.');
    }
}