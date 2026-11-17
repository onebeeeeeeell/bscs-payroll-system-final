<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class DepartmentService
{
    private const SANITIZED_TEXT_FIELDS = ['department_name'];

    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $search = $this->sanitizeSearchTerm($search);

        return Department::query()
            ->withCount('employees')
            // only filter when something was typed in the search box (ilike = case-insensitive in PostgreSQL)
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('department_name', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->paginate($perPage)
            ->withQueryString(); // keep ?search= when changing pages
    }

    public function create(array $data): Department
    {
        $data = $this->sanitizeTextFields($data);

        // transaction so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => Department::create($data)
        );
    }

    public function update(Department $department, array $data): Department
    {
        $data = $this->sanitizeTextFields($data);

        return DB::transaction(function () use ($department, $data) {
            $department->update($data);

            return $department;
        });
    }

    public function delete(Department $department): void
    {
        DB::transaction(
            fn () => $department->delete()
        );
    }

    private function sanitizeSearchTerm(?string $search): ?string
    {
        return $search === null ? null : $this->sanitizeTextFields(['department_name' => $search])['department_name'];
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

    // diriiiiiiiiiiiiiiiiii sugpon if may ano sa controller na logic
}