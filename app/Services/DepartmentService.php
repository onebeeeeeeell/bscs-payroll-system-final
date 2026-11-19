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
            ->latest()
            ->paginate($perPage)
            ->withQueryString(); // keep ?search= when changing pages
    }


    public function create(array $data): Department
    {
        $data = $this->sanitizeFields($data);

        // transaction so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => Department::create($data)
        );
    }


    public function update(Department $department, array $data): Department
    {
        $data = $this->sanitizeFields($data);

        return DB::transaction(function () use ($department, $data) {
            $department->update($data);

            return $department;
        });
    }


    public function delete(Department $department): void
    {
        DB::transaction(function () use ($department) {
            // RESTRICT ang FK sa employees: kapag may employee pa, mag-eerror ang
            // database (500). Mas malinaw na validation message ang ibinabalik dito.
            if ($department->employees()->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'department' => 'This department still has employees and cannot be deleted.',
                ]);
            }

            $department->delete();
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


    // diriiiiiiiiiiiiiiiiii sugpon if may ano sa controller na logic
}