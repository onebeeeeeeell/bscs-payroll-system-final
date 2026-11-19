<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait FiltersByDateParts
{
    /*
     * LABEL: Date parts scope
     * Purpose: I-filter by year / month / day (isa lang o kombinasyon) sa isa
     * o higit pang date column. Kapag higit sa isang column, tumatama ang row
     * kapag ANG ALINMAN sa mga column ay pasok sa napiling bahagi.
     * Walang laman na year/month/day = walang ia-apply.
     *
     * @param string|list<string> $columns
     * @param array{year?: int|string|null, month?: int|string|null, day?: int|string|null} $parts
     */
    public function scopeMatchingDateParts(Builder $query, string|array $columns, array $parts): void
    {
        $columns = (array) $columns;

        foreach (['year' => 'Year', 'month' => 'Month', 'day' => 'Day'] as $key => $suffix) {
            if (! filled($parts[$key] ?? null)) {
                continue;
            }

            $value = (int) $parts[$key];

            $query->where(function (Builder $dates) use ($columns, $suffix, $value) {
                foreach ($columns as $index => $column) {
                    $dates->{($index === 0 ? 'where' : 'orWhere').$suffix}($column, $value);
                }
            });
        }
    }
}