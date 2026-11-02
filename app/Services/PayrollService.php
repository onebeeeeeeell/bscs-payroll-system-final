<?php

namespace App\Services;

use App\Models\Payroll;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

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
        // transaction so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => Payroll::create($data)
        );
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