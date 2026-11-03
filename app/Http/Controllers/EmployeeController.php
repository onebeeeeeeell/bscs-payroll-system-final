<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use App\Services\EmployeeService;
use App\Http\Requests\Employee\EmployeeRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct(
        private EmployeeService $employeeService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('employee/index', [
            'employees' => $this->employeeService->paginate(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('employee/create', [
            'positions' => Position::query()->orderBy('title')->get(['id', 'title']),
            'departments' => Department::query()->orderBy('department_name')->get(['id', 'department_name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmployeeRequest $request): RedirectResponse
    {
        $this->employeeService->create($request->validated());

        return to_route('employees.index')->with('success', 'Employee created successfully.');
    }

    /**
     * Display the specified resource.
     */
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

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee): Response
    {
        return Inertia::render('employee/edit', [
            'employee' => $employee,
            'positions' => Position::query()->orderBy('title')->get(['id', 'title']),
            'departments' => Department::query()->orderBy('department_name')->get(['id', 'department_name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->employeeService->update($employee, $request->validated());

        return to_route('employees.index')->with('success', 'Employee updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        $this->employeeService->delete($employee);

        return to_route('employees.index')->with('success', 'Employee deleted successfully.');
    }
}
