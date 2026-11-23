<?php

namespace App\Services;

use App\Models\EmploymentStatus;
use App\Models\Employee;
use App\Services\Concerns\SanitizesText;
use Illuminate\Support\Facades\DB;

class EmploymentStatusService
{
    use SanitizesText;

    private const SANITIZED_FIELDS = ['name'];

    public function create(array $data): EmploymentStatus
    {
        $data = $this->sanitizeFields($data);

        return EmploymentStatus::create($data);
    }


    public function update(EmploymentStatus $status, array $data): EmploymentStatus
    {
        $data = $this->sanitizeFields($data);

        return DB::transaction(function () use ($status, $data) {
            $oldName = $status->name;
            $status->update($data);

            if ($oldName !== $status->name) {
                Employee::query()
                    ->withStatus($oldName)
                    ->update(['status' => $status->name]);
            }


            return $status;
        });
    }


    public function deleteIfUnused(EmploymentStatus $status): bool
    {
        return DB::transaction(function () use ($status) {
            EmploymentStatus::query()->whereKey($status->getKey())->lockForUpdate()->firstOrFail();

            if (Employee::query()->withStatus($status->name)->exists()) {
                return false;
            }

            $status->delete();

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