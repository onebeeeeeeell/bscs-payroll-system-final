<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListSearchRequest;
use App\Http\Requests\Payslip\PayslipRequest;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Services\PayslipService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PayslipController extends Controller
{
    public function __construct(
        private PayslipService $payslipService,
    ) {}

    public function index(ListSearchRequest $request): Response
    {
        $search = $request->validated('search');

        return Inertia::render('payslip/index', [
            'payslips' => $this->payslipService->paginate($search),
            'filters' => ['search' => $search ?? ''],
        ]);
    }

    public function create(): Response
    {
        // Mga payroll na wala pang payslip, pinakabago muna. Naka-cap para hindi bumigat ang dropdown.
        return Inertia::render('payslip/create', [
            'payrolls' => Payroll::query()
                ->doesntHave('payslip')
                ->with([
                    'employee:id,first_name,last_name,suffix',
                    'payrollPeriod:id,start_date,end_date',
                ])
                ->latest('date_processed')
                ->limit(300)
                ->get(['id', 'employee_id', 'payroll_period_id', 'net_pay', 'date_processed']),
        ]);
    }

    public function store(PayslipRequest $request): RedirectResponse
    {
        $this->payslipService->create($request->validated());

        return to_route('payslips.index')->with('success', 'Payslip created successfully.');
    }

    public function edit(Payslip $payslip): Response
    {
        return Inertia::render('payslip/edit', [
            'payslip' => $payslip->load(['payroll.employee', 'payroll.payrollPeriod']),
        ]);
    }

    public function update(PayslipRequest $request, Payslip $payslip): RedirectResponse
    {
        $this->payslipService->update($payslip, $request->validated());

        return to_route('payslips.index')->with('success', 'Payslip updated successfully.');
    }

    public function destroy(Payslip $payslip): RedirectResponse
    {
        $this->payslipService->delete($payslip);

        return to_route('payslips.index')->with('success', 'Payslip deleted successfully.');
    }
}