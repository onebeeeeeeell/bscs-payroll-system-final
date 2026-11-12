<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContributionType\ContributionTypeRequest;
use App\Models\ContributionType;
use App\Services\ContributionTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContributionTypeController extends Controller
{
    public function __construct(
        private \App\Services\ContributionTypeService $contributionTypeService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('contribution-type/index', [
            'contributionTypes' => $this->contributionTypeService->paginate($request->query('search')),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('contribution-type/create');
    }

    public function store(ContributionTypeRequest $request): RedirectResponse
    {
        $this->contributionTypeService->create($request->validated());

        return to_route('contribution_types.index')->with('success', 'Contribution type created successfully.');
    }

    public function edit(ContributionType $contributionType): Response
    {
        return Inertia::render('contribution-type/edit', [
            'contributionType' => $contributionType,
        ]);
    }

    public function update(ContributionTypeRequest $request, ContributionType $contributionType): RedirectResponse
    {
        $this->contributionTypeService->update($contributionType, $request->validated());

        return to_route('contribution_types.index')->with('success', 'Contribution type updated successfully.');
    }

    public function destroy(ContributionType $contributionType): RedirectResponse
    {
        $this->contributionTypeService->delete($contributionType);

        return to_route('contribution_types.index')->with('success', 'Contribution type deleted successfully.');
    }
}