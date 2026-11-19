<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payroll\ProcessPayrollRequest;
use App\Services\PayrollProcessingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PayrollProcessController extends Controller
{
    public function __construct(
        private PayrollProcessingService $payrollProcessingService,
    ) {}

    /*
     * LABEL: Process payroll page
     * Purpose: Pipili ng period, makikita ang computed preview (walang
     * sine-save) bago i-save.
     */
    public function create(ProcessPayrollRequest $request): Response
    {
        return Inertia::render('payroll/create', $this->payrollProcessingService->pageData($request->validated()));
    }

    /*
     * LABEL: Save processed payroll
     * Purpose: Ang user na naka-login ang naka-record na nag-process.
     */
    public function store(ProcessPayrollRequest $request): RedirectResponse
    {
        $this->payrollProcessingService->process(
            (int) $request->validated('payroll_period_id'),
            (int) $request->user()->id,
        );

        return to_route('payrolls.index')->with('success', 'Payroll processed successfully.');
    }
}