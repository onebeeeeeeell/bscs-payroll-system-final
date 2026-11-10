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
            ->with(['employee:id,first_name,last_name,suffix', 'payrollPeriod:id,start_date,end_date,pay_date,status', 'user:id,name'])
            // only filter when something was typed in the search box (ilike = case-insensitive in PostgreSQL)
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->whereHas('employee', fn ($employee) => $employee
                    ->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%"));
            }))
            ->latest('date_processed')
            ->paginate($perPage)
            ->withQueryString(); // keep ?search= when changing pages
    }

    public function create(array $data): Payroll
    {
        return DB::transaction(function () use ($data) {
            $employeeIsActive = Employee::query()
                ->whereKey($data['employee_id'])
                ->whereNull('archived_at')
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