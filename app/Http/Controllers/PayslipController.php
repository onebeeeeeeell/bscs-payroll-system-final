<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payslip\PayslipEmployeeRecordsRequest;
use App\Http\Requests\Payslip\PayslipIndexRequest;
use App\Http\Requests\Payslip\PayslipRequest;
use App\Models\Payslip;
use App\Services\PayslipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
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

        return Inertia::render('payslip/index', $this->payslipService->indexData($filters));
    }

    // View in the payslip list: all payslips of one employee
    public function employeeRecords(PayslipEmployeeRecordsRequest $request, int $employeeId): Response
    {
        return Inertia::render('payslip/employee-records', $this->payslipService->employeeData(
            $employeeId,
            $request->filters(),
        ));
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

    // Archive / restore from the payslip list: every payslip of one employee
    // (within the date filter, if any). The page and filters stay as they were.
    public function archiveEmployee(PayslipEmployeeRecordsRequest $request, int $employeeId): RedirectResponse
    {
        $count = $this->payslipService->archiveEmployeePayslips($employeeId, $request->filters());

        return back()->with('success', $this->countMessage($count, 'archived'));
    }

    public function restoreEmployee(PayslipEmployeeRecordsRequest $request, int $employeeId): RedirectResponse
    {
        $count = $this->payslipService->restoreEmployeePayslips($employeeId, $request->filters());

        return back()->with('success', $this->countMessage($count, 'restored'));
    }

    /*
     * LABEL: Archive payslip
     * Purpose: Ang DELETE route ay nag-a-archive na (walang permanenteng burahan).
     * Archive at restore ay nasa employee payslips page, kaya bumabalik sa
     * page (at filters) na pinanggalingan.
     */
    public function destroy(Payslip $payslip): RedirectResponse
    {
        $this->payslipService->archive($payslip);

        return back()->with('success', 'Payslip archived successfully.');
    }

    public function restore(Payslip $payslip): RedirectResponse
    {
        $this->payslipService->restore($payslip);

        return back()->with('success', 'Payslip restored successfully.');
    }

    private function countMessage(int $count, string $action): string
    {
        return "{$count} ".Str::plural('payslip', $count)." {$action} successfully.";
    }
}