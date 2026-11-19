<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayrollPeriod\PayrollPeriodIndexRequest;
use App\Http\Requests\PayrollPeriod\PayrollPeriodRequest;
use App\Models\PayrollPeriod;
use App\Services\PayrollPeriodService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PayrollPeriodController extends Controller
{
    public function __construct(
        private PayrollPeriodService $payrollPeriodService,
    ) {}

    public function index(PayrollPeriodIndexRequest $request): Response
    {
        $filters = $request->filters();

        return Inertia::render('payroll-period/index', [
            'payrollPeriods' => $this->payrollPeriodService->paginate($filters),
            'filters' => $filters,
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

    /*
     * LABEL: Archive period
     * Purpose: Ang DELETE route ay nag-a-archive na (walang permanenteng burahan).
     */
    public function destroy(PayrollPeriodIndexRequest $request, PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $this->payrollPeriodService->archive($payrollPeriod);

        return to_route('payroll_periods.index', $request->redirectFilters())->with('success', 'Payroll period archived successfully.');
    }

    public function restore(PayrollPeriodIndexRequest $request, PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $this->payrollPeriodService->restore($payrollPeriod);

        return to_route('payroll_periods.index', $request->redirectFilters())->with('success', 'Payroll period restored successfully.');
    }
}