<?php

namespace App\Models;

use App\Models\Concerns\Archivable;
use App\Models\Concerns\FiltersByDateParts;
use App\Models\Concerns\HasSearchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
    'start_date',
    'end_date',
    'pay_date',
    'status',
    'archived_at',
])]

class PayrollPeriod extends Model
{
    use HasFactory, HasSearchScope, Archivable, FiltersByDateParts;

    public const PROCESSABLE_STATUSES = ['Pending', 'Failed'];

    /*
     * LABEL: Payrolls count scope
     * Purpose: Idagdag ang payrolls_count sa bawat payroll period.
     */
    public function scopeWithPayrollsCount(Builder $query): void
    {
        $query->withCount('payrolls');
    }

    /*
     * LABEL: Filters scope
     * Purpose: status ('active' / 'archived'), search (status ng period), at
     * year / month / day. Tumatama ang date filter kapag ang start_date O
     * end_date ng period ay pasok sa napiling year / month / day.
     *
     * @param array<string, mixed> $filters
     */
    public function scopeForFilters(Builder $query, array $filters): void
    {
        $query
            ->withArchiveStatus($filters['status'] ?? null)
            ->search($filters['search'] ?? null)
            ->matchingDateParts(['start_date', 'end_date'], $filters);
    }

    /*
     * LABEL: Processable scope
     * Purpose: Mga period na pwede pang i-process (Pending o Failed), pinakabago
     * muna. Piling columns lang para sa dropdown.
     */
    public function scopeProcessable(Builder $query): void
    {
        $query->active()
            ->whereIn('status', self::PROCESSABLE_STATUSES)
            ->latest('start_date')
            ->select(['id', 'start_date', 'end_date', 'pay_date', 'status']);
    }

    /*
     * LABEL: Lock by id scope
     * Purpose: Isang period lang, naka-lock (SELECT ... FOR UPDATE) habang nasa
     * transaction, para hindi sabay ma-process ng dalawang user. Tinatawag
     * bilang ->lockById($id)->first().
     */
    public function scopeLockById(Builder $query, int $periodId): void
    {
        $query->whereKey($periodId)->lockForUpdate();
    }

    public function isProcessable(): bool
    {
        return in_array($this->status, self::PROCESSABLE_STATUSES, true);
    }

    protected function searchColumn(): string
    {
        return 'status';
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'payroll_period_id', 'id');
    }

}