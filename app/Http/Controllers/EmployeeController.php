<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Employee;
use App\Services\EmployeeService;
use App\Http\Requests\Employee\EmployeeIndexRequest;
use App\Http\Requests\Employee\EmployeeRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct(
        private EmployeeService $employeeService,
    ) {}

    public function index(EmployeeIndexRequest $request): Response
    {
        $validated = $request->validated();
        $filters = $request->filters();

        return Inertia::render('employee/index', $this->employeeService->indexData($validated, $filters));
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
        $employee->load([
            'position:id,title',
            'department:id,department_name',
        ]);

        return Inertia::render('employee/show', [
            'employee' => $employee,
        ]);
    }

    public function edit(Employee $employee): Response
    {
        abort_if($employee->archived_at !== null, 404);

        return Inertia::render('employee/edit', array_merge([
            'employee' => $employee,
        ], $this->employeeService->employeeEditOptions()));
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        abort_if($employee->archived_at !== null, 404);

        $this->employeeService->update($employee, $request->validated());

        return to_route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(Request $request, Employee $employee): RedirectResponse
    {
        $this->employeeService->archive($employee);

        return to_route('employees.index', $request->only([
            'status',
            'department_id',
            'position_id',
            'hire_year',
            'hire_month',
        ]))->with('success', 'Employee archived successfully.');
    }

    public function restore(Request $request, Employee $employee): RedirectResponse
    {
        $this->employeeService->restore($employee);

        return to_route('employees.index', $request->only([
            'status',
            'department_id',
            'position_id',
            'hire_year',
            'hire_month',
        ]))->with('success', 'Employee restored successfully.');
    }
}