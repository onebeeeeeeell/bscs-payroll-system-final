<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\Employee;
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


            return Payroll::create($data);
        });
    }


    public function update(Payroll $payroll, array $data): Payroll
    {
        return DB::transaction(function () use ($payroll, $data) {
            $payroll->update($data);

            return $payroll;
        });
    }


    public function delete(Payroll $payroll): void
    {
        DB::transaction(
            fn () => $payroll->delete()
        );
    }
}