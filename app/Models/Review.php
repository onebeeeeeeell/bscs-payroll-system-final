<?php

namespace App\Models;

use App\Models\Concerns\Archivable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

// approved_at at archived_at ay hindi kasama: ang admin action lang ang puwedeng mag-set nito.
#[Fillable([
    'name',
    'email',
    'rating',
    'comment',
])]

class Review extends Model
{
    use Archivable;

    public const MIN_RATING = 1;
    public const MAX_RATING = 5;

    // Walang email: hindi dapat lumabas sa public page.
    public const PUBLIC_COLUMNS = ['id', 'name', 'rating', 'comment', 'created_at'];

    /*
     * LABEL: Published scope
     * Purpose: Approved na ng admin at hindi naka-archive -> ito lang ang nakikita sa public page.
     */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('approved_at')->active();
    }

    /*
     * LABEL: Pending scope
     * Purpose: Hindi pa approved at hindi naka-archive -> ang pila ng admin.
     */
    public function scopePending(Builder $query): void
    {
        $query->whereNull('approved_at')->active();
    }

    /*
     * LABEL: Status scope
     * Purpose: Para sa admin list: 'approved' -> published(), 'archived' -> archived(),
     * kahit ano pa (o null) -> pending().
     */
    public function scopeForStatus(Builder $query, ?string $status): void
    {
        match ($status) {
            'approved' => $query->published(),
            'archived' => $query->archived(),
            default => $query->pending(),
        };
    }

    public function scopeLatestFirst(Builder $query): void
    {
        $query->latest()->latest('id');
    }

    /*
     * LABEL: Rating counts scope
     * Purpose: Bilang ng review bawat rating (1-5) sa isang query lang. Dito kinukuha
     * ng ReviewService ang total at average, kaya hindi na kailangan ng hiwalay na count/avg query.
     */
    public function scopeRatingCounts(Builder $query): void
    {
        $query->select('rating')
            ->selectRaw('count(*) as total')
            ->groupBy('rating');
    }

    public function approve(): void
    {
        $this->forceFill(['approved_at' => now()])->save();
    }
}