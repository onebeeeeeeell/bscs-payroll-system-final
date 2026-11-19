<?php

namespace App\Models;

use App\Models\Concerns\Archivable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
    'employee_id',
    'payroll_period_id',
    'user_id',
    'gross_pay',
    'total_deductions',
    'net_pay',
    'date_processed',
    'archived_at',
])]

class Payroll extends Model
{
    use HasFactory, Archivable;

    /*
     * LABEL: Locked period statuses
     * Purpose: Kapag ang period ng payroll ay may ganitong status, hindi na
     * pwedeng i-edit o i-delete ang payroll record (bayad na).
     */
    // Walang naka-lock na status: pwedeng i-edit/i-archive ang payroll kahit Paid na
    // ang period. Maglagay ulit ng ['Paid'] dito para ibalik ang lock.
    public const LOCKED_PERIOD_STATUSES = [];

    /*
     * LABEL: Eager load details
     * Purpose: I-load na agad ang employee, payrollPeriod, at user
     * (iwas N+1 problem). Piling columns lang para magaan ang query.
     */
    public function scopeWithDetails(Builder $query): void
    {
        $query->with([
            'employee:id,first_name,last_name,suffix',
            'payrollPeriod:id,start_date,end_date,pay_date,status',
            'user:id,name',
        ]);
    }

    /*
     * LABEL: Period scope
     * Purpose: I-filter by payroll_period_id. Tinatawag bilang ->forPeriod($id).
     */
    public function scopeForPeriod(Builder $query, int $payrollPeriodId): void
    {
        $query->where('payroll_period_id', $payrollPeriodId);
    }

    /*
     * LABEL: Filters scope
     * Purpose: Lahat ng filters ng Payroll records page sa iisang scope.
     * - status: 'archived' o default 'active'.
     * - date: eksaktong araw ng date_processed (Y-m-d).
     * - search / department_id / position_id: sa employee ng payroll (isang
     *   whereHas lang kahit lahat ng tatlo ang gamit).
     *
     * @param array<string, mixed> $filters
     */
    public function scopeForFilters(Builder $query, array $filters): void
    {
        $search = $filters['search'] ?? null;
        $departmentId = $filters['department_id'] ?? null;
        $positionId = $filters['position_id'] ?? null;

        $query
            ->withArchiveStatus($filters['status'] ?? null)
            // range sa halip na whereDate() para magamit ang index ng column
            ->when(filled($filters['date'] ?? null), fn (Builder $query) => $query->whereBetween('date_processed', [
                Carbon::parse($filters['date'])->startOfDay(),
                Carbon::parse($filters['date'])->endOfDay(),
            ]))
            ->when(filled($search) || filled($departmentId) || filled($positionId), fn (Builder $query) => $query->whereHas(
                'employee',
                fn (Builder $employee) => $employee
                    ->when(filled($search), fn (Builder $employee) => $employee->matchingFirstOrLastName($search))
                    ->when(filled($departmentId), fn (Builder $employee) => $employee->inDepartment($departmentId))
                    ->when(filled($positionId), fn (Builder $employee) => $employee->inPosition($positionId)),
            ));
    }

    /*
     * LABEL: Locked check
     * Purpose: True kapag bayad na ang period. Gumagamit ng loaded na
     * payrollPeriod relation (naka-load sa controller/service).
     */
    public function isLocked(): bool
    {
        return in_array($this->payrollPeriod?->status, self::LOCKED_PERIOD_STATUSES, true);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(Deduction::class, 'payroll_id', 'id');
    }

    public function payslip(): HasOne
    {
        return $this->hasOne(Payslip::class, 'payroll_id', 'id');
    }

}