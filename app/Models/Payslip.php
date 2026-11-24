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

}