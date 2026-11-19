<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
    'date_processed'
])]

class Payroll extends Model
{
    use HasFactory;

    /*
     * LABEL: Locked period statuses
     * Purpose: Kapag ang period ng payroll ay may ganitong status, hindi na
     * pwedeng i-edit o i-delete ang payroll record (bayad na).
     */
    public const LOCKED_PERIOD_STATUSES = ['Paid'];

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

    public function scopeSearch(Builder $query, ?string $search): void
    {
        if ($search) {
            $query->whereHas('employee', fn (Builder $employee) => $employee->matchingFirstOrLastName($search));
        }
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