<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/*
 * LABEL: Archivable
 * Purpose: Soft archive gamit ang archived_at column (Payroll, PayrollPeriod,
 * Payslip). Isang lugar lang ang scopes, cast, at archive()/restore().
 */
trait Archivable
{
    public function initializeArchivable(): void
    {
        $this->mergeCasts(['archived_at' => 'datetime']);
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    /*
     * LABEL: Archive status scope
     * Purpose: 'archived' -> archived(); kahit ano pa (o null) -> active().
     */
    public function scopeWithArchiveStatus(Builder $query, ?string $status): void
    {
        $status === 'archived' ? $query->archived() : $query->active();
    }

    public function archive(): void
    {
        $this->forceFill(['archived_at' => now()])->save();
    }

    public function restore(): void
    {
        $this->forceFill(['archived_at' => null])->save();
    }
}