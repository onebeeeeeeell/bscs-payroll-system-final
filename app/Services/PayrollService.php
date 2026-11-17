<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\Employee;
use App\Services\Concerns\SanitizesText;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    use SanitizesText;

    private const SANITIZED_FIELDS = [
        'employee_id',
        'payroll_period_id',
        'user_id',
        'gross_pay',
        'total_deductions',
        'net_pay',
        'date_processed',
    ];

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
        $data = $this->sanitizeFields($data);

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
        $data = $this->sanitizeFields($data);

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


    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeFields(array $data): array
    {
        return $this->sanitizeStringFields($data, self::SANITIZED_FIELDS, false);
    }


    // diriiiiiiiiiiiiiiiiiiii sugpon if may ano sa controller na logic
}