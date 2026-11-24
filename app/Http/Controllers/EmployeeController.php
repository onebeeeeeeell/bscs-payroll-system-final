<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Employee;
use App\Services\EmployeeService;
use App\Http\Requests\Employee\EmployeeIndexRequest;
use App\Http\Requests\Employee\EmployeeRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct(
        private EmployeeService $employeeService,
    ) {}

    public function index(EmployeeIndexRequest $request): Response
    {
        $filters = $request->filters();

        return Inertia::render('employee/index', $this->employeeService->indexData($filters));
    }

    public function create(): Response
    {
        return Inertia::render('employee/create', $this->employeeService->employeeEditOptions());
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        $this->employeeService->create($request->validated());

        return to_route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee): Response
    {
        return Inertia::render('employee/show', [
            'employee' => $employee->loadShowDetails(),
        ]);
    }

    public function edit(Employee $employee): Response
    {
        return Inertia::render('employee/edit', $this->employeeService->editData($employee));
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->employeeService->update($employee, $request->validated());

        return to_route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(EmployeeIndexRequest $request, Employee $employee): RedirectResponse
    {
        $this->employeeService->archive($employee);

        return to_route('employees.index', $request->redirectFilters())->with('success', 'Employee archived successfully.');
    }

    public function restore(EmployeeIndexRequest $request, Employee $employee): RedirectResponse
    {
        $this->employeeService->restore($employee);

        return to_route('employees.index', $request->redirectFilters())->with('success', 'Employee restored successfully.');
    }
}