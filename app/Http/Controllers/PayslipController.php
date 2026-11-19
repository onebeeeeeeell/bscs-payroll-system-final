<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payslip\PayslipIndexRequest;
use App\Http\Requests\Payslip\PayslipRequest;
use App\Models\Department;
use App\Models\Payslip;
use App\Models\Position;
use App\Services\PayslipService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PayslipController extends Controller
{
    public function __construct(
        private PayslipService $payslipService,
    ) {}

    public function index(PayslipIndexRequest $request): Response
    {
        $filters = $request->filters();

        return Inertia::render('payslip/index', [
            'payslips' => $this->payslipService->paginate($filters),
            'departments' => Department::options(),
            'positions' => Position::options(),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        // Mga payroll na wala pang payslip (ang query ay nasa Payroll::awaitingPayslip)
        return Inertia::render('payslip/create', [
            'payrolls' => $this->payslipService->payrollOptions(),
        ]);
    }

    public function store(PayslipRequest $request): RedirectResponse
    {
        $payslip = $this->payslipService->create($request->validated());

        // Diretso sa payslip preview pagkatapos mag-generate
        return to_route('payslips.show', $payslip)->with('success', 'Payslip generated successfully.');
    }

    /*
     * LABEL: Payslip preview
     * Purpose: Ang mismong payslip (earnings, deductions, net pay) na pwedeng
     * i-print o i-save bilang PDF.
     */
    public function show(Payslip $payslip): Response
    {
        return Inertia::render('payslip/show', $this->payslipService->statement($payslip));
    }

    /*
     * LABEL: Archive payslip
     * Purpose: Ang DELETE route ay nag-a-archive na (walang permanenteng burahan).
     */
    public function destroy(PayslipIndexRequest $request, Payslip $payslip): RedirectResponse
    {
        $this->payslipService->archive($payslip);

        return to_route('payslips.index', $request->redirectFilters())->with('success', 'Payslip archived successfully.');
    }

    public function restore(PayslipIndexRequest $request, Payslip $payslip): RedirectResponse
    {
        $this->payslipService->restore($payslip);

        return to_route('payslips.index', $request->redirectFilters())->with('success', 'Payslip restored successfully.');
    }
}