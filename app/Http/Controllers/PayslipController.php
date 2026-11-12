<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payslip\PayslipRequest;
use App\Models\Payslip;
use App\Services\PayslipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayslipController extends Controller
{
    public function __construct(
        private PayslipService $payslipService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('payslip/index', [
            'payslips' => $this->payslipService->paginate(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('payslip/create');
    }

    public function store(PayslipRequest $request): RedirectResponse
    {
        $this->payslipService->create($request->validated());

        return to_route('payslips.index')->with('success', 'Payslip created successfully.');
    }

    public function edit(Payslip $payslip): Response
    {
        return Inertia::render('payslip/edit', [
            'payslip' => $payslip->load('payroll.employee'),
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