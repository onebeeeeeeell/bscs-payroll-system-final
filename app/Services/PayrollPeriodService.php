<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class PayrollPeriodService
{
    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return PayrollPeriod::query()
            ->withPayrollsCount()
            ->search($search)
            ->latest('start_date')
            ->paginate($perPage)
            ->withQueryString(); // keep ?search= when changing pages
    }


    public function create(array $data): PayrollPeriod
    {
        // transaction so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => PayrollPeriod::create($data)
        );
    }


    public function update(PayrollPeriod $payrollPeriod, array $data): PayrollPeriod
    {
        return DB::transaction(function () use ($payrollPeriod, $data) {
            $payrollPeriod->update($data);

            return $payrollPeriod;
        });
    }


    public function delete(PayrollPeriod $payrollPeriod): void
    {
        DB::transaction(function () use ($payrollPeriod) {
            // Naka-cascade ang FK: ang pagbura ng period ay buburahin din ang lahat
            // ng payrolls (at payslips) nito. Kaya empty na period lang ang pwedeng i-delete.
            $hasPayrolls = $payrollPeriod->payrolls()->lockForUpdate()->exists();

            if ($hasPayrolls) {
                throw ValidationException::withMessages([
                    'payroll_period' => 'This period already has payroll records and cannot be deleted.',
                ]);
            }

            $payrollPeriod->delete();
        });
    }


    // diriiiiiiiiiiiiiiiiii sugpon if may ano sa controller na logic
}