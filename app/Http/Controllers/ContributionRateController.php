<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\ContributionRate;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\ListSearchRequest;
use App\Services\ContributionRateService;
use App\Http\Requests\ContributionRate\ContributionRateRequest;

class ContributionRateController extends Controller
{
    public function __construct(
        private ContributionRateService $contributionRateService,
    ) {}

    public function index(ListSearchRequest $request): Response
    {
        return Inertia::render('contribution-rate/index', [
            'contributionRates' => $this->contributionRateService->paginate($request->validated('search')),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('contribution-rate/create', [
            'contributionTypes' => $this->contributionRateService->typeOptions(),
        ]);
    }

    public function store(ContributionRateRequest $request): RedirectResponse
    {
        $this->contributionRateService->create($request->validated());

        return to_route('contribution_rates.index')->with('success', 'Contribution rate created successfully.');
    }

    public function edit(int $contributionRateId): Response
    {
        return Inertia::render('contribution-rate/edit', $this->contributionRateService->editData($contributionRateId));
    }

    public function update(ContributionRateRequest $request, ContributionRate $contributionRate): RedirectResponse
    {
        $this->contributionRateService->update($contributionRate, $request->validated());

        return to_route('contribution_rates.index')->with('success', 'Contribution rate updated successfully.');
    }

    public function destroy(ContributionRate $contributionRate): RedirectResponse
    {
        $this->contributionRateService->delete($contributionRate);

        return to_route('contribution_rates.index')->with('success', 'Contribution rate deleted successfully.');
    }
}