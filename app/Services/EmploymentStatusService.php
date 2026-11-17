<?php

namespace App\Services;

use App\Models\EmploymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmploymentStatusService
{
    private const SANITIZED_TEXT_FIELDS = ['name'];

    public function create(array $data): EmploymentStatus
    {
        $data = $this->sanitizeTextFields($data);

        return DB::transaction(
            fn () => EmploymentStatus::create($data)
        );
    }

    public function update(EmploymentStatus $status, array $data): EmploymentStatus
    {
        $data = $this->sanitizeTextFields($data);

        return DB::transaction(function () use ($status, $data) {
            $oldName = $status->name;
            $status->update($data);

            DB::table('employees')->where('status', $oldName)->update(['status' => $status->name]);

            return $status;
        });
    }

    public function deleteIfUnused(Model $model, callable $isInUse, string $label): void
    {
        DB::transaction(function () use ($model, $isInUse, $label) {
            $model->newQuery()->whereKey($model->getKey())->lockForUpdate()->firstOrFail();

            if ($isInUse()) {
                throw ValidationException::withMessages([
                    'message' => "This {$label} cannot be deleted because employee records are using it.",
                ]);
            }

            $model->delete();
        });
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
}
