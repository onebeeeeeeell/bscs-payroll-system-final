<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payroll\PayrollRequest;
use App\Models\Payroll;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollController extends Controller
{
    public function __construct(
        private PayrollService $payrollService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('payroll/index', [
            'payrolls' => $this->payrollService->paginate($request->query('search')),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('payroll/create');
    }

    public function store(PayrollRequest $request): RedirectResponse
    {
        $this->payrollService->create($request->validated());

        return to_route('payrolls.index')->with('success', 'Payroll created successfully.');
    }

    public function edit(Payroll $payroll): Response
    {
        return Inertia::render('payroll/edit', [
            'payroll' => $payroll->load(['employee', 'payrollPeriod', 'user']),
        ]);
    }

    public function update(PayrollRequest $request, Payroll $payroll): RedirectResponse
    {
        $this->payrollService->update($payroll, $request->validated());

        return to_route('payrolls.index')->with('success', 'Payroll updated successfully.');
    }

    public function destroy(Payroll $payroll): RedirectResponse
    {
        $this->payrollService->delete($payroll);

        return to_route('payrolls.index')->with('success', 'Payroll deleted successfully.');
    }
}