<?php

namespace App\Models;

use App\Models\Concerns\Archivable;
use App\Models\Concerns\FiltersListsByEmployeeAndDay;
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
    'date_processed',
])]

class Payroll extends Model
{
    use HasFactory, Archivable, FiltersListsByEmployeeAndDay;

    /*
     * LABEL: Locked period statuses
     * Purpose: Kapag ang period ng payroll ay may ganitong status, hindi na
     * pwedeng i-edit o i-delete ang payroll record (bayad na).
     */
    // Walang naka-lock na status: pwedeng i-edit/i-archive ang payroll kahit Paid na
    // ang period. Maglagay ulit ng ['Paid'] dito para ibalik ang lock.
    public const LOCKED_PERIOD_STATUSES = [];

    public const MAX_GROSS_PAY = 30000;

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

    public function scopeLatestFirst(Builder $query): void
    {
        $query->latest('date_processed')->latest('id');
    }

    /*
     * LABEL: Filters scope
     * Purpose: Lahat ng filters ng Payroll records page sa iisang scope.
     * - status: 'archived' o default 'active'.
     * - date: eksaktong araw ng date_processed (Y-m-d).
     * - search / department_id / position_id: sa employee ng payroll.
     *
     * @param array<string, mixed> $filters
     */
    public function scopeForFilters(Builder $query, array $filters): void
    {
        $query
            ->withArchiveStatus($filters['status'] ?? null)
            ->onDay('date_processed', $filters['date'] ?? null)
            ->forEmployeeFilters('employee', $filters);
    }

    /*
     * LABEL: Without payslip scope
     * Purpose: Mga payroll na wala pang payslip. Ginagamit ng awaitingPayslip()
     * at ng dashboard count, kaya iisa lang ang logic.
     */
    public function scopeWithoutPayslip(Builder $query): void
    {
        $query->doesntHave('payslip');
    }

    /*
     * LABEL: Summary scope
     * Purpose: Aggregates para sa dashboard, kasama ang payrolls na wala pang
     * payslip sa isang database round trip. COALESCE para 0 ang lumabas, hindi
     * null, kapag wala pang payroll.
     */
    public function scopeSummary(Builder $query): void
    {
        $query
            ->selectRaw('COUNT(*) AS payrolls')
            ->selectRaw('COALESCE(SUM(gross_pay), 0) AS gross_pay')
            ->selectRaw('COALESCE(SUM(total_deductions), 0) AS total_deductions')
            ->selectRaw('COALESCE(SUM(net_pay), 0) AS net_pay')
            ->selectSub(
                static::query()
                    ->active()
                    ->withoutPayslip()
                    ->select([])
                    ->selectRaw('COUNT(*)'),
                'awaiting_payslip',
            );
    }

    /*
     * LABEL: Recent scope
     * Purpose: Pinakabagong payroll muna (date_processed, tapos id kung
     * pareho ang araw), kasama ang employee at period (piling columns lang).
     * Para sa "Recent payrolls" ng dashboard.
     */
    public function scopeRecent(Builder $query, int $limit = 5): void
    {
        $query
            ->with([
                'employee:id,first_name,last_name,suffix',
                'payrollPeriod:id,start_date,end_date',
            ])
            ->latest('date_processed')
            ->latest('id')
            ->limit($limit);
    }

    /*
     * LABEL: Awaiting payslip scope
     * Purpose: Mga active na payroll na wala pang payslip, pinakabago muna,
     * para sa dropdown ng Generate payslip. Piling columns lang, naka-cap
     * para hindi bumigat ang dropdown.
     */
    public function scopeAwaitingPayslip(Builder $query, int $limit = 300): void
    {
        $query->active()
            ->withoutPayslip()
            ->with([
                'employee:id,first_name,last_name,suffix',
                'payrollPeriod:id,start_date,end_date',
            ])
            ->latest('date_processed')
            ->limit($limit)
            ->select(['id', 'employee_id', 'payroll_period_id', 'net_pay', 'date_processed']);
    }

    /*
     * LABEL: Load details
     * Purpose: Mga relation na kailangan ng show at edit page. Isang lugar na
     * lang para hindi na nasa controller ang mga listahan. loadMissing: hindi
     * na uulitin ang query sa relation na naka-load na.
     */
    public function loadShowDetails(): static
    {
        return $this->loadMissing([
            'employee:id,first_name,last_name,suffix',
            'payrollPeriod:id,start_date,end_date,pay_date,status',
            'user:id,name',
            'deductions.contributionRate.contributionType:id,name',
            'payslip:id,payroll_id,date_generated',
        ]);
    }

    public function loadEditDetails(): static
    {
        return $this->loadMissing([
            'employee:id,first_name,last_name,suffix',
            'payrollPeriod:id,start_date,end_date,pay_date,status',
            'user:id,name',
        ]);
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