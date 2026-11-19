<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListSearchRequest;
use App\Http\Requests\Payroll\PayrollRequest;
use App\Models\Payroll;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PayrollController extends Controller
{
    public function __construct(
        private PayrollService $payrollService,
    ) {}

    public function index(ListSearchRequest $request): Response
    {
        return Inertia::render('payroll/index', [
            'payrolls' => $this->payrollService->paginate($request->validated('search')),
        ]);
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