<?php

namespace App\Services;

use App\Models\EmploymentStatus;
use App\Models\Employee;
use App\Services\Concerns\SanitizesText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmploymentStatusService
{
    use SanitizesText;

    private const SANITIZED_FIELDS = ['name'];

    public function create(array $data): EmploymentStatus
    {
        $data = $this->sanitizeFields($data);

        return DB::transaction(
            fn () => EmploymentStatus::create($data)
        );
    }


    public function update(EmploymentStatus $status, array $data): EmploymentStatus
    {
        $data = $this->sanitizeFields($data);

        return DB::transaction(function () use ($status, $data) {
            $oldName = $status->name;
            $status->update($data);


            Employee::query()
                ->withStatus($oldName)
                ->update(['status' => $status->name]);


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


    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeFields(array $data): array
    {
        return $this->sanitizeStringFields($data, self::SANITIZED_FIELDS);
    }

}