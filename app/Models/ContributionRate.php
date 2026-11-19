<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
    'contribution_type_id',
    'calculation_type',
    'rate'
])]

class ContributionRate extends Model
{
    use HasFactory;

    public const TYPE_FIXED = 'fixed';

    public const TYPE_PERCENT = 'percent';

    public const CALCULATION_TYPES = [self::TYPE_FIXED, self::TYPE_PERCENT];

    /*
     * LABEL: Amount for gross
     * Purpose: Halaga ng deduction para sa isang employee. Fixed = ang rate mismo;
     * percent = rate% ng gross pay ng employee sa period (rounded sa 2 decimals).
     */
    public function amountFor(float $gross): float
    {
        if ($this->calculation_type === self::TYPE_PERCENT) {
            return round($gross * (float) $this->rate / 100, 2);
        }

        return round((float) $this->rate, 2);
    }

    /*
     * LABEL: Eager load contribution type
     * Purpose: with() para i-load na agad ang contributionType (iwas N+1 problem).
     * ':id,name' = piling columns lang para magaan ang query.
     */
    public function scopeWithType(Builder $query): void
    {
        $query->with(['contributionType:id,name']);
    }

    /*
     * LABEL: Latest rate per type scope
     * Purpose: Ang pinakabagong rate (pinakamataas na id) ng bawat contribution
     * type, para sa payroll deductions. Isang query lang, may subquery.
     */
    public function scopeLatestPerType(Builder $query): void
    {
        $query->whereIn('id', fn ($latest) => $latest
            ->from('contribution_rates')
            ->selectRaw('MAX(id)')
            ->groupBy('contribution_type_id'));
    }

    public function scopeSearch(Builder $query, ?string $search): void
    {
        if ($search) {
            $query->whereHas('contributionType', fn (Builder $type) => $type->search($search));
        }
    }

    /*
     * LABEL: Used check
     * Purpose: True kapag may deductions na na gumamit ng rate na ito (nasa payroll na).
     */
    public function isUsed(): bool
    {
        return $this->deductions()->exists();
    }

    public function contributionType(): BelongsTo
    {
        return $this->belongsTo(ContributionType::class, 'contribution_type_id', 'id');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(Deduction::class, 'contribution_rate_id', 'id');
    }

}