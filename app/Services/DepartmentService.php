<?php

namespace App\Services;

use App\Models\Department;
use App\Services\Concerns\SanitizesText;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class DepartmentService
{
    use SanitizesText;

    private const SANITIZED_FIELDS = ['department_name'];

    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return Department::query()
            ->withEmployeesCount()
            ->search($this->sanitizeSearch($search))
            ->latestFirst()
            ->paginate($perPage)
            ->withQueryString(); // keep ?search= when changing pages
    }


    public function create(array $data): Department
    {
        $data = $this->sanitizeFields($data);

        return Department::create($data);
    }


    public function update(Department $department, array $data): Department
    {
        $data = $this->sanitizeFields($data);

        $department->update($data);

        return $department;
    }


    public function delete(Department $department): void
    {
        if (! $this->deleteIfUnused($department)) {
            throw ValidationException::withMessages([
                'department' => 'This department still has employees and cannot be deleted.',
            ]);
        }
    }

    public function deleteIfUnused(Department $department): bool
    {
        return DB::transaction(function () use ($department) {
            Department::query()->whereKey($department->getKey())->lockForUpdate()->firstOrFail();

            if ($department->employees()->exists()) {
                return false;
            }

            $department->delete();

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