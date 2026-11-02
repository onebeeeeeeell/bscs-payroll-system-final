<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\ContributionType;
use App\Http\Requests\ContributionType\ContributionTypeRequest;

class ContributionTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contributionTypes = ContributionType::select(['id', 'name'])->with([
            'contribution_rate:id,contribution_type_id,rate',
        ])->get();
        
        dd ($contributionTypes);

        return Inertia::render('contribution-type/index', [
            'contributionTypes' => $contributionTypes,
        ]);
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ContributionTypeRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ContributionType $contributionType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ContributionType $contributionType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ContributionTypeRequest $request, ContributionType $contributionType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ContributionType $contributionType)
    {
        //
    }
}
