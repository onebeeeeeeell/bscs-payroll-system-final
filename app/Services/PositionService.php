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
            ->search($search)
            ->latest()
            ->paginate($perPage)
            ->withQueryString(); // it keep ?search= when changing pages
    }


    public function create(array $data): Position
    {
        $data = $this->sanitizeFields($data);

        // transaction, so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => Position::create($data)
        );
    }


    public function update(Position $position, array $data): Position
    {
        $data = $this->sanitizeFields($data);

        return DB::transaction(function () use ($position, $data) {
            $position->update($data);

            return $position;
        });
    }


    public function delete(Position $position): void
    {
        DB::transaction(function () use ($position) {
            // RESTRICT ang FK sa employees: kapag may employee pa, mag-eerror ang
            // database (500). Mas malinaw na validation message ang ibinabalik dito.
            if ($position->employees()->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'position' => 'This position still has employees and cannot be deleted.',
                ]);
            }

            $position->delete();
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


    // diriiiiiiiiiiiiiiiiiiiiiii sugpon if may ano sa controller na logic
}