<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayrollPeriod\PayrollPeriodRequest;
use App\Models\PayrollPeriod;
use App\Services\PayrollPeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollPeriodController extends Controller
{
    public function __construct(
        private PayrollPeriodService $payrollPeriodService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('payroll-period/index', [
            'payrollPeriods' => $this->payrollPeriodService->paginate($request->query('search')),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('payroll-period/create');
    }

    public function store(PayrollPeriodRequest $request): RedirectResponse
    {
        $this->payrollPeriodService->create($request->validated());

        return to_route('payroll_periods.index')->with('success', 'Payroll period created successfully.');
    }

    public function edit(PayrollPeriod $payrollPeriod): Response
    {
        return Inertia::render('payroll-period/edit', [
            'payrollPeriod' => $payrollPeriod,
        ]);
    }

    public function update(PayrollPeriodRequest $request, PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $this->payrollPeriodService->update($payrollPeriod, $request->validated());

        return to_route('payroll_periods.index')->with('success', 'Payroll period updated successfully.');
    }

    public function destroy(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $this->payrollPeriodService->delete($payrollPeriod);

        return to_route('payroll_periods.index')->with('success', 'Payroll period deleted successfully.');
    }
}
