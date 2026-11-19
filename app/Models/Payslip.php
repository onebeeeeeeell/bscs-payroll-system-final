<?php

namespace App\Models;

use App\Models\Concerns\Archivable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
    'payroll_id',
    'date_generated',
    'archived_at',
])]

class Payslip extends Model
{
    use HasFactory, Archivable;

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

    /*
     * LABEL: Filters scope
     * Purpose: status ('active' / 'archived'), date (eksaktong araw ng
     * date_generated), at search / department / position. Ang tatlong huli ay
     * nasa employee ng payroll, kaya iisang whereHas lang (isang EXISTS).
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
            ->when(filled($filters['date'] ?? null), fn (Builder $query) => $query->whereBetween('date_generated', [
                Carbon::parse($filters['date'])->startOfDay(),
                Carbon::parse($filters['date'])->endOfDay(),
            ]))
            ->when(filled($search) || filled($departmentId) || filled($positionId), fn (Builder $query) => $query->whereHas(
                'payroll.employee',
                fn (Builder $employee) => $employee
                    ->when(filled($search), fn (Builder $employee) => $employee->matchingFirstOrLastName($search))
                    ->when(filled($departmentId), fn (Builder $employee) => $employee->inDepartment($departmentId))
                    ->when(filled($positionId), fn (Builder $employee) => $employee->inPosition($positionId)),
            ));
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'payroll_id', 'id');
    }

}