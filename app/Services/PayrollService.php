<?php


namespace App\Services;


use App\Models\Payroll;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;


class PayrollService
{
    private const SANITIZED_TEXT_FIELDS = ['search'];


    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $search = $this->sanitizeSearchTerm($search);


        return Payroll::query()
            ->with(['employee:id,first_name,last_name,suffix', 'payrollPeriod:id,start_date,end_date,pay_date,status', 'user:id,name'])
            // only filter when something was typed in the search box (like is case-insensitive for plain text in SQLite and MySQL)
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->whereHas('employee', fn ($employee) => $employee
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%"));
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


    private function sanitizeSearchTerm(?string $search): ?string
    {
        return $search === null ? null : $this->sanitizeTextFields(['search' => $search])['search'];
    }


    private function sanitizeTextFields(array $data): array
    {
        foreach (self::SANITIZED_TEXT_FIELDS as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim(strip_tags($data[$field]));
            }
        }


        return $data;
    }


    // diriiiiiiiiiiiiiiiiiiii sugpon if may ano sa controller na logic
}