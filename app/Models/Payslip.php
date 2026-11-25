<?php

namespace App\Models;

use App\Models\Concerns\Archivable;
use App\Models\Concerns\FiltersListsByEmployeeAndDay;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
    'payroll_id',
    'date_generated',
])]

class Payslip extends Model
{
    use HasFactory, Archivable, FiltersListsByEmployeeAndDay;

    /*
     * LABEL: Eager load details
     * Purpose: I-load na agad ang payroll at ang employee nito
     * (iwas N+1 problem). Piling columns lang para magaan ang query.
     */
    public function scopeWithDetails(Builder $query): void
    {
        $query->with([
            'payroll:id,employee_id,gross_pay,total_deductions,net_pay',
            'payroll.employee:id,first_name,last_name,suffix',
        ]);
    }

    public function scopeLatestFirst(Builder $query): void
    {
        $query->latest('date_generated')->latest('id');
    }

    /*
     * LABEL: Filters scope
     * Purpose: status ('active' / 'archived'), date (eksaktong araw ng
     * date_generated), at search / department / position sa employee ng payroll.
     *
     * @param array<string, mixed> $filters
     */
    public function scopeForFilters(Builder $query, array $filters): void
    {
        $query
            ->withArchiveStatus($filters['status'] ?? null)
            ->onDay('date_generated', $filters['date'] ?? null)
            ->forEmployeeFilters('payroll.employee', $filters);
    }

    /*
     * LABEL: Date bounds scope
     * Purpose: Pinakauna at pinakahuling date_generated (kasama ang archived),
     * para sa year range ng date filter. ISANG query lang.
     */
    public function scopeDateBounds(Builder $query): void
    {
        $query->selectRaw('MIN(date_generated) AS first_date, MAX(date_generated) AS last_date');
    }

    /*
     * LABEL: Eager load employee
     * Purpose: Para sa per-employee list: ang employee_id ay galing sa
     * summaryPerEmployee() (walang payroll_id ang grouped row). Piling columns lang.
     */
    public function scopeWithEmployee(Builder $query): void
    {
        $query->with('employee:id,first_name,last_name,suffix');
    }

    /*
     * LABEL: Eager load payroll and period
     * Purpose: Para sa employee payslips page: ang payroll (amounts) at ang
     * period nito (iwas N+1 problem). Piling columns lang.
     */
    public function scopeWithPayrollPeriod(Builder $query): void
    {
        $query->with([
            'payroll:id,employee_id,payroll_period_id,gross_pay,total_deductions,net_pay',
            'payroll.payrollPeriod:id,start_date,end_date',
        ]);
    }

    /*
     * LABEL: Join payroll scope
     * Purpose: Para sa aggregates na kailangan ang net_pay at employee_id ng
     * payroll. Ginagamit lang kasama ang select ng aggregates (hindi sa listahan
     * ng mga row), kaya walang banggaan ng column names.
     */
    public function scopeJoinPayroll(Builder $query): void
    {
        $query->join('payrolls', 'payrolls.id', '=', 'payslips.payroll_id');
    }

    /*
     * LABEL: Totals summary scope
     * Purpose: Aggregates ng mga payslip na tumutugma sa filters, ISANG query
     * lang: payslips_count, total_net_pay, last_generated. COALESCE para 0 ang
     * lumabas, hindi null, kapag walang payslip.
     */
    public function scopeTotalsSummary(Builder $query): void
    {
        $query
            ->joinPayroll()
            ->selectRaw('COUNT(*) AS payslips_count')
            ->selectRaw('COALESCE(SUM(payrolls.net_pay), 0) AS total_net_pay')
            ->selectRaw('MAX(payslips.date_generated) AS last_generated');
    }

    /*
     * LABEL: Summary per employee scope
     * Purpose: Isang row bawat employee para sa payslip list (GROUP BY
     * employee_id ng payroll). Ang lahat ng payslip ng employee ay nasa
     * employee payslips page (View). Sinusundan ang forFilters().
     */
    public function scopeSummaryPerEmployee(Builder $query): void
    {
        $query
            ->totalsSummary()
            ->addSelect('payrolls.employee_id')
            ->groupBy('payrolls.employee_id');
    }

    /*
     * LABEL: Latest activity first scope
     * Purpose: Sorting ng summaryPerEmployee(): pinakabagong last_generated
     * muna. employee_id ang tie-breaker para stable ang pages.
     */
    public function scopeLatestActivityFirst(Builder $query): void
    {
        $query->orderByDesc('last_generated')->orderBy('employee_id');
    }

    /*
     * LABEL: Employee scope
     * Purpose: Mga payslip ng isang employee (sa pamamagitan ng payroll nito).
     * Tinatawag bilang ->forEmployee($id).
     */
    public function scopeForEmployee(Builder $query, int $employeeId): void
    {
        $query->whereHas('payroll', fn (Builder $payroll) => $payroll->where('employee_id', $employeeId));
    }

    /*
     * LABEL: Employee payslips filters scope
     * Purpose: Filters ng employee payslips page: status (active/archived) at
     * date generated. Walang search/department/position kasi isang employee
     * lang ang tinitingnan.
     *
     * @param array{status?: 'active'|'archived', date?: string|null} $filters
     */
    public function scopeForEmployeeRecords(Builder $query, int $employeeId, array $filters): void
    {
        $query
            ->forEmployee($employeeId)
            ->withArchiveStatus($filters['status'] ?? null)
            ->onDay('date_generated', $filters['date'] ?? null);
    }

    public function loadStatementDetails(): static
    {
        return $this->loadMissing([
            'payroll.employee:id,first_name,last_name,suffix,position_id,department_id,daily_rate',
            'payroll.employee.position:id,title',
            'payroll.employee.department:id,department_name',
            'payroll.payrollPeriod:id,start_date,end_date,pay_date,status',
            'payroll.user:id,name',
            'payroll.deductions.contributionRate:id,contribution_type_id,calculation_type,rate',
            'payroll.deductions.contributionRate.contributionType:id,name',
        ]);
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'payroll_id', 'id');
    }

    // Para lang sa scopeSummaryPerEmployee(): ang grouped row ay may employee_id (galing sa payroll)
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

}