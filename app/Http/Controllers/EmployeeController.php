<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Employee;
use App\Models\Department;
use App\Models\EmploymentStatus;
use App\Models\Position;
use App\Services\EmployeeService;
use App\Http\Requests\Employee\EmployeeRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct(
        private EmployeeService $employeeService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'hire_year' => ['nullable', 'integer', 'between:1900,9999'],
            'hire_month' => ['nullable', 'integer', 'between:1,12'],
            'status' => ['nullable', 'in:active,archived'],
        ]);
        $filters = [
            'department_id' => $validated['department_id'] ?? '',
            'position_id' => $validated['position_id'] ?? '',
            'hire_year' => $validated['hire_year'] ?? '',
            'hire_month' => $validated['hire_month'] ?? '',
            'status' => $validated['status'] ?? 'active',
        ];

        $firstHireDate = Employee::min('date_hired');
        $lastHireDate = Employee::max('date_hired');
        $firstYear = $firstHireDate ? Carbon::parse($firstHireDate)->year : now()->year;
        $lastYear = $lastHireDate ? max(now()->year, Carbon::parse($lastHireDate)->year) : now()->year;

        return Inertia::render('employee/index', [
            'employees' => $this->employeeService->paginate($validated),
            'departments' => Department::query()->orderBy('department_name')->get(['id', 'department_name']),
            'positions' => Position::query()->orderBy('title')->get(['id', 'title']),
            'hireYears' => range($lastYear, $firstYear),
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('employee/create', [
<<<<<<< HEAD
            'positions' => $this->employeeService->kuhaSangPosition(),
            'departments' => $this->employeeService->kuhaSangDepartment(),
=======
            'positions' => Position::query()->orderBy('title')->get(['id', 'title']),
            'departments' => Department::query()->orderBy('department_name')->get(['id', 'department_name']),
            'statuses' => EmploymentStatus::query()->orderBy('name')->get(['id', 'name']),
>>>>>>> 58d2f63a03ebb8998a66465371f01d72067ebf0c
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
        abort_if($employee->archived_at !== null, 404);

        return Inertia::render('employee/edit', [
            'employee' => $employee,
            'positions' => Position::query()->orderBy('title')->get(['id', 'title']),
            'departments' => Department::query()->orderBy('department_name')->get(['id', 'department_name']),
            'statuses' => EmploymentStatus::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => EmploymentStatus::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        abort_if($employee->archived_at !== null, 404);

        $this->employeeService->update($employee, $request->validated());

        return to_route('employees.index')->with('success', 'Employee updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
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
