<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use App\Models\PayrollPeriod;
use App\Services\Concerns\SanitizesText;
use App\Support\YearBounds;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    use SanitizesText;

    /**
     * @param array<string, mixed> $filters search, status, date, department_id, position_id
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return Payroll::query()
            ->withDetails()
            ->forFilters($this->sanitizeFilters($filters))
            ->latestFirst()
            ->paginate($perPage)
            ->withQueryString(); // keep the filters when changing pages
    }

    public function indexData(array $filters): array
    {
        return [
            'payrolls' => $this->paginate($filters),
            'departments' => Department::options(),
            'positions' => Position::options(),
            'filters' => $filters,
            // closure: only computed when the page asks for it (not on filter reloads)
            'dateBounds' => fn () => $this->dateBounds(),
        ];
    }


    /*
     * Year range of the date filter: current year, plus any year that has a payroll
     * record (archived ones too), so past records can always be found.
     *
     * @return array{min: string, max: string}
     */
    private function dateBounds(): array
    {
        $dates = Payroll::query()->dateBounds()->first();

        return YearBounds::covering([$dates->first_date, $dates->last_date]);
    }


    // Payroll na may employee, period, at user para sa edit page.
    public function forEdit(Payroll $payroll): Payroll
    {
        return $payroll->loadEditDetails();
    }

    public function create(array $data): Payroll
    {
        $data = Arr::only($data, [
            'employee_id',
            'payroll_period_id',
            'user_id',
            'gross_pay',
            'total_deductions',
            'date_processed',
        ]);
        $data = $this->normalizeAmounts($data, ['gross_pay', 'total_deductions']);
        $this->assertGrossPayWithinLimit($data['gross_pay']);

        return DB::transaction(function () use ($data) {
            $employeeIsActive = Employee::query()
                ->activeForPayrollRecord((int) $data['employee_id'])
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
        $this->assertNotArchived($payroll, 'gross_pay');

        $data = $this->normalizeAmounts($data, ['gross_pay', 'total_deductions']);
        $this->assertGrossPayWithinLimit($data['gross_pay']);

        return DB::transaction(function () use ($payroll, $data) {
            $this->assertNotLocked($payroll, 'gross_pay');

            $attributes = [
                'gross_pay' => $data['gross_pay'],
                'total_deductions' => $data['total_deductions'],
                'date_processed' => $this->processedAt($data['date_processed'] ?? null),
                'net_pay' => $this->netPay($data['gross_pay'], $data['total_deductions']),
            ];

            $payroll->update($attributes);

            return $payroll;
        });
    }


    /*
     * LABEL: Archive payroll
     * Purpose: Soft archive; walang binubura (kasama ang deductions at
     * payslip), kaya pwede itong ibalik.
     */
    public function archive(Payroll $payroll): void
    {
        $this->assertNotArchived($payroll, 'payroll');

        DB::transaction(function () use ($payroll) {
            $this->assertNotLocked($payroll, 'payroll');

            $payroll->archive();
        });
    }

    public function restore(Payroll $payroll): void
    {
        $payroll->restore();
    }


    /*
     * LABEL: Archived guard
     * Purpose: Hindi puwedeng baguhin o i-archive ulit ang naka-archive na payroll,
     * kahit gawan ng request nang mano-mano (hindi dumaan sa page). Restore muna.
     */
    private function assertNotArchived(Payroll $payroll, string $errorKey): void
    {
        if ($payroll->archived_at !== null) {
            throw ValidationException::withMessages([
                $errorKey => 'Archived payrolls cannot be changed. Restore it first.',
            ]);
        }
    }


    /*
     * LABEL: Lock guard
     * Purpose: Hinaharang ang edit/delete kapag Paid na ang period. Binabasa
     * ang status na naka-lock (SELECT ... FOR UPDATE) sa loob ng transaction
     * para hindi makalusot kung sabay na minarkahang Paid ang period.
     */
    private function assertNotLocked(Payroll $payroll, string $errorKey): void
    {
        // Walang naka-lock na status = walang kailangang i-query
        if (Payroll::LOCKED_PERIOD_STATUSES === []) {
            return;
        }

        $status = PayrollPeriod::query()
            ->lockById((int) $payroll->payroll_period_id)
            ->value('status');

        if (in_array($status, Payroll::LOCKED_PERIOD_STATUSES, true)) {
            throw ValidationException::withMessages([
                $errorKey => 'This payroll belongs to a paid period and can no longer be changed.',
            ]);
        }
    }


    /*
     * LABEL: Processed at
     * Purpose: Petsa (Y-m-d) + oras ngayon, para may oras pa rin ang audit
     * trail ng payslip. Ang "ngayong araw lang" ay binabantayan ng PayrollRequest;
     * kapag blangko o hindi tunay na petsa ang dumating, ngayon ang gagamitin.
     */
    private function processedAt(?string $date): Carbon
    {
        return Carbon::parse($this->sanitizeDate($date) ?: 'today')->setTimeFrom(now());
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

    private function assertGrossPayWithinLimit(float|int|string $grossPay): void
    {
        if ((float) $grossPay > Payroll::MAX_GROSS_PAY) {
            throw ValidationException::withMessages([
                'gross_pay' => 'Gross pay cannot exceed 30,000.00.',
            ]);
        }
    }
}