<?php

namespace App\Http\Controllers;

use App\Http\Requests\Position\PositionRequest;
use App\Models\Position;
use App\Services\PositionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PositionController extends Controller
{
    public function __construct(
        private PositionService $positionService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('position/index', [
            'positions' => $this->positionService->paginate($request->query('search')),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('position/create');
    }

    public function store(PositionRequest $request): RedirectResponse
    {
        $this->positionService->create($request->validated());

        return to_route('positions.index')->with('success', 'Position created successfully.');
    }

    public function edit(Position $position): Response
    {
        return Inertia::render('position/edit', [
            'position' => $position,
        ]);
    }

    public function update(PositionRequest $request, Position $position): RedirectResponse
    {
        $this->positionService->update($position, $request->validated());

        return to_route('positions.index')->with('success', 'Position updated successfully.');
    }

    public function destroy(Position $position): RedirectResponse
    {
        $this->positionService->delete($position);

        return to_route('positions.index')->with('success', 'Position deleted successfully.');
    }
}