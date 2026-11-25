<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/*
 * LABEL: Year bounds
 * Purpose: Range ng calendar (date picker) ng mga filter. Current year ang
 * default; lumalawak lang ito kung may record sa ibang taon. Walang record sa
 * nakaraang taon = hindi lalabas ang nakaraang taon; walang record sa susunod
 * na taon = hindi lalabas ang susunod na taon.
 */
final class YearBounds
{
    /**
     * @param array<int, string|null> $dates mga petsang nasa database (null ay hindi binibilang)
     * @return array{min: string, max: string} Jan 1 ng pinakaunang taon, Dec 31 ng pinakahuling taon
     */
    public static function covering(array $dates): array
    {
        $current = now()->year;
        $years = array_map(fn (string $date) => Carbon::parse($date)->year, array_filter($dates));

        return [
            'min' => min([$current, ...$years]).'-01-01',
            'max' => max([$current, ...$years]).'-12-31',
        ];
    }
}