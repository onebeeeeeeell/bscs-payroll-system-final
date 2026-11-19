<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return Payroll::query()
            ->withDetails()
            ->search($search)
            ->latest('date_processed')
            ->paginate($perPage)
            ->withQueryString(); // keep ?search= when changing pages
    }


    public function create(array $data): Payroll
    {
        return DB::transaction(function () use ($data) {
            $employeeIsActive = Employee::query()
                ->whereKey($data['employee_id'])
                ->active()
                ->lockForUpdate()
                ->exists();


            if (! $employeeIsActive) {
                throw ValidationException::withMessages([
                    'employee_id' => 'Archived employees cannot be included in new payroll records.',
                ]);
            }

            $data['net_pay'] = $this->netPay($data['gross_pay'], $data['total_deductions']);

            return Payroll::create($data);
        });
    }


    /*
     * LABEL: Update payroll
     * Purpose: Tatlong field lang ang pwedeng baguhin (gross, deductions,
     * date_processed). Ang net_pay ay kino-compute dito sa server; hindi
     * galing sa request. Ang employee, period, at user_id ay hindi na nagagalaw.
     */
    public function update(Payroll $payroll, array $data): Payroll
    {
        return DB::transaction(function () use ($payroll, $data) {
            $this->assertNotLocked($payroll, 'gross_pay');

            $attributes = [
                'gross_pay' => $data['gross_pay'],
                'total_deductions' => $data['total_deductions'],
                'date_processed' => $data['date_processed'],
                'net_pay' => $this->netPay($data['gross_pay'], $data['total_deductions']),
            ];

            $payroll->update($attributes);

            return $payroll;
        });
    }


    public function delete(Payroll $payroll): void
    {
        DB::transaction(function () use ($payroll) {
            $this->assertNotLocked($payroll, 'payroll');

            $payroll->delete();
        });
    }


    /*
     * LABEL: Lock guard
     * Purpose: Hinaharang ang edit/delete kapag Paid na ang period. Binabasa
     * ang status na naka-lock (SELECT ... FOR UPDATE) sa loob ng transaction
     * para hindi makalusot kung sabay na minarkahang Paid ang period.
     */
    private function assertNotLocked(Payroll $payroll, string $errorKey): void
    {
        $status = PayrollPeriod::query()
            ->whereKey($payroll->payroll_period_id)
            ->lockForUpdate()
            ->value('status');

        if (in_array($status, Payroll::LOCKED_PERIOD_STATUSES, true)) {
            throw ValidationException::withMessages([
                $errorKey => 'This payroll belongs to a paid period and can no longer be changed.',
            ]);
        }
    }


    private function netPay(float|int|string $gross, float|int|string $deductions): float
    {
        $net = round((float) $gross - (float) $deductions, 2);

        if ($net < 0) {
            throw ValidationException::withMessages([
                'total_deductions' => 'Deductions cannot be more than gross pay.',
            ]);
        }

        return $net;
    }
}