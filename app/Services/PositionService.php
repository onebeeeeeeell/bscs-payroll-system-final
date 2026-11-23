<?php

namespace App\Services;

use App\Models\Position;
use App\Services\Concerns\SanitizesText;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class PositionService
{
    use SanitizesText;

    private const SANITIZED_FIELDS = ['title'];

    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return Position::query()
            ->withEmployeesCount()
            ->search($this->sanitizeSearch($search))
            ->latestFirst()
            ->paginate($perPage)
            ->withQueryString(); // it keep ?search= when changing pages
    }


    public function create(array $data): Position
    {
        $data = $this->sanitizeFields($data);

        return Position::create($data);
    }


    public function update(Position $position, array $data): Position
    {
        $data = $this->sanitizeFields($data);

        $position->update($data);

        return $position;
    }


    public function delete(Position $position): void
    {
        if (! $this->deleteIfUnused($position)) {
            throw ValidationException::withMessages([
                'position' => 'This position still has employees and cannot be deleted.',
            ]);
        }
    }

    public function deleteIfUnused(Position $position): bool
    {
        return DB::transaction(function () use ($position) {
            Position::query()->whereKey($position->getKey())->lockForUpdate()->firstOrFail();

            if ($position->employees()->exists()) {
                return false;
            }

            $position->delete();

            return true;
        });
    }


    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeFields(array $data): array
    {
        return $this->sanitizeStringFields($data, self::SANITIZED_FIELDS);
    }
}