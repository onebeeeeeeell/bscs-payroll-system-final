<?php

namespace App\Http\Requests\Payroll;

use App\Http\Requests\ListFiltersRequest;

class PayrollIndexRequest extends ListFiltersRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,archived'],
            'year' => ['nullable', 'integer', 'between:1900,9999'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'day' => ['nullable', 'integer', 'between:1,31'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
        ];
    }

    protected function defaults(): array
    {
        return [
            'search' => '',
            'status' => 'active',
            'year' => '',
            'month' => '',
            'day' => '',
            'department_id' => '',
            'position_id' => '',
        ];
    }
}