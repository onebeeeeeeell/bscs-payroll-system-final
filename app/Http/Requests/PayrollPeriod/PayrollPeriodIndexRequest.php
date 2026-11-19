<?php

namespace App\Http\Requests\PayrollPeriod;

use App\Http\Requests\ListFiltersRequest;

class PayrollPeriodIndexRequest extends ListFiltersRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,archived'],
            'year' => ['nullable', 'integer', 'between:1900,9999'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'day' => ['nullable', 'integer', 'between:1,31'],
        ];
    }

    protected function defaults(): array
    {
        return ['search' => '', 'status' => 'active', 'year' => '', 'month' => '', 'day' => ''];
    }
}