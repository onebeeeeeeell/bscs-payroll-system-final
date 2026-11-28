<?php

namespace App\Services;

use App\Models\Review;
use App\Services\Concerns\SanitizesText;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    use SanitizesText;

    // Ito lang ang fields na puwedeng pumasok galing sa labas (hindi kasama ang approved_at / archived_at / website).
    private const SUBMIT_FIELDS = ['name', 'email', 'rating', 'comment'];

    // Free text lang ang nililinis dito. Ang rating ay validated na sa ReviewRequest.
    private const SANITIZED_TEXT_FIELDS = ['name', 'email', 'comment'];

    private const LATEST_LIMIT = 6;

    /**
     * Bagong review: naghihintay muna ng approval ng admin (approved_at = null).
     *
     * @param array<string, mixed> $data
     *
     * @throws ValidationException
     */
    public function submit(array $data): Review
    {
        $data = $this->sanitizeStringFields(Arr::only($data, self::SUBMIT_FIELDS), self::SANITIZED_TEXT_FIELDS);
        $data['email'] = Str::lower($data['email']);

        try {
            return Review::create($data);
        } catch (UniqueConstraintViolationException) {
            // Dalawang sabay na submit gamit ang parehong email: ang database ang huling bantay
            throw ValidationException::withMessages([
                'email' => 'This email has already submitted a review.',
            ]);
        }
    }

    /**
     * Para sa public welcome page: average, bilang bawat star, at ang pinakabagong approved na reviews.
     * Dalawang query lang: isa para sa bilang bawat rating, isa para sa listahan.
     *
     * @return array{summary: array{average: float, total: int, breakdown: list<array{rating: int, total: int}>}, latest: Collection<int, Review>}
     */
    public function publicData(): array
    {
        $counts = Review::query()
            ->published()
            ->ratingCounts()
            ->pluck('total', 'rating');

        $breakdown = [];
        $total = 0;
        $points = 0;

        for ($rating = Review::MAX_RATING; $rating >= Review::MIN_RATING; $rating--) {
            $count = (int) ($counts[$rating] ?? 0);

            $breakdown[] = ['rating' => $rating, 'total' => $count];
            $total += $count;
            $points += $rating * $count;
        }

        return [
            'summary' => [
                'average' => $total > 0 ? round($points / $total, 1) : 0.0,
                'total' => $total,
                'breakdown' => $breakdown,
            ],
            'latest' => Review::query()
                ->published()
                ->latestFirst()
                ->limit(self::LATEST_LIMIT)
                ->get(Review::PUBLIC_COLUMNS),
        ];
    }

    /**
     * Para sa admin page: pending (default), approved, o archived. Ang maling status ay pending.
     */
    public function paginate(?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return Review::query()
            ->forStatus($status)
            ->latestFirst()
            ->paginate($perPage)
            ->withQueryString(); // keep ?status= when changing pages
    }

    public function approve(Review $review): void
    {
        $review->approve();
    }

    public function archive(Review $review): void
    {
        $review->archive();
    }

    public function restore(Review $review): void
    {
        $review->restore();
    }
}