<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payroll\PayrollIndexRequest;
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

    public function index(PayrollIndexRequest $request): Response
    {
        $filters = $request->filters();

        return Inertia::render('payroll/index', $this->payrollService->indexData($filters));
    }

    public function show(Payroll $payroll): Response
    {
        return Inertia::render('payroll/show', [
            'payroll' => $payroll->loadShowDetails(),
        ]);
    }

    public function edit(Payroll $payroll): Response|RedirectResponse
    {
        $payroll = $this->payrollService->forEdit($payroll);

        // Naka-lock lang kapag may laman ang Payroll::LOCKED_PERIOD_STATUSES.
        if ($payroll->isLocked()) {
            return to_route('payrolls.show', $payroll);
        }

        return Inertia::render('payroll/edit', [
            'payroll' => $payroll,
        ]);
    }

    public function update(PayrollRequest $request, Payroll $payroll): RedirectResponse
    {
        $this->payrollService->update($payroll, $request->validated());

        return to_route('payrolls.index')->with('success', 'Payroll updated successfully.');
    }

    /*
     * LABEL: Archive payroll
     * Purpose: Ang DELETE route ay nag-a-archive na (walang permanenteng burahan).
     */
    public function destroy(PayrollIndexRequest $request, Payroll $payroll): RedirectResponse
    {
        $this->payrollService->archive($payroll);

        return to_route('payrolls.index', $request->redirectFilters())->with('success', 'Payroll archived successfully.');
    }

    public function restore(PayrollIndexRequest $request, Payroll $payroll): RedirectResponse
    {
        $this->payrollService->restore($payroll);

        return to_route('payrolls.index', $request->redirectFilters())->with('success', 'Payroll restored successfully.');
    }
}