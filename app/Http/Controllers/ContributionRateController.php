<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListSearchRequest;
use App\Http\Requests\ContributionRate\ContributionRateRequest;
use App\Models\ContributionRate;
use App\Models\ContributionType;
use App\Services\ContributionRateService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContributionRateController extends Controller
{
    public function __construct(
        private ContributionRateService $contributionRateService,
    ) {}

    public function index(ListSearchRequest $request): Response
    {
        return Inertia::render('contribution-rate/index', [
            'contributionRates' => $this->contributionRateService->paginate($request->validated('search')),
            // Ang pinakabagong rate ng bawat type ang ginagamit sa payroll
            'currentRateIds' => ContributionRate::query()->latestPerType()->pluck('id'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('contribution-rate/create', [
            'contributionTypes' => ContributionType::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(ContributionRateRequest $request): RedirectResponse
    {
        $this->contributionRateService->create($request->validated());

        return to_route('contribution_rates.index')->with('success', 'Contribution rate created successfully.');
    }

    public function edit(ContributionRate $contributionRate): Response
    {
        return Inertia::render('contribution-rate/edit', [
            'contributionRate' => $contributionRate->load('contributionType'),
            // True kapag nagamit na sa payroll: view lang, hindi na mababago
            'locked' => $contributionRate->deductions()->exists(),
        ]);
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