<?php

namespace App\Http\Controllers;

use App\Http\Requests\Department\DepartmentRequest;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function __construct(
        private DepartmentService $departmentService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('department/index', [
            'departments' => $this->departmentService->paginate($request->query('search')),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('department/create');
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        $this->departmentService->create($request->validated());

        return to_route('departments.index')->with('success', 'Department created successfully.');
    }

    public function edit(Department $department): Response
    {
        return Inertia::render('department/edit', [
            'department' => $department,
        ]);
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $this->departmentService->update($department, $request->validated());

        return to_route('departments.index')->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->departmentService->delete($department);

        return to_route('departments.index')->with('success', 'Department deleted successfully.');
    }
}