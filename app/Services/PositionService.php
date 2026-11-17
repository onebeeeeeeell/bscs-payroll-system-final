<?php

namespace App\Services;

use App\Models\Position;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class PositionService
{
    private const SANITIZED_TEXT_FIELDS = ['title'];

    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $search = $this->sanitizeSearchTerm($search);

        return Position::query()
            ->withCount('employees')
            
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('title', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->paginate($perPage)
            ->withQueryString(); // it keep ?search= when changing pages
    }

    public function create(array $data): Position
    {
        $data = $this->sanitizeTextFields($data);

        // transaction, so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => Position::create($data)
        );
    }

    public function update(Position $position, array $data): Position
    {
        $data = $this->sanitizeTextFields($data);

        return DB::transaction(function () use ($position, $data) {
            $position->update($data);

            return $position;
        });
    }

    public function delete(Position $position): void
    {
        DB::transaction(
            fn () => $position->delete()
        );
    }

    private function sanitizeSearchTerm(?string $search): ?string
    {
        return $search === null ? null : $this->sanitizeTextFields(['title' => $search])['title'];
    }

    private function sanitizeTextFields(array $data): array
    {
        foreach (self::SANITIZED_TEXT_FIELDS as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim(strip_tags($data[$field]));
            }
        }

        return $data;
    }

    // diriiiiiiiiiiiiiiiiiiiiiii sugpon if may ano sa controller na logic
}