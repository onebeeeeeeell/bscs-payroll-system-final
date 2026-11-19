<?php

namespace App\Http\Requests\PayrollPeriod;

use App\Http\Requests\ListFiltersRequest;

class PayrollPeriodIndexRequest extends ListFiltersRequest
{
    public function rules(): array
    {
        return [
            'search' => $this->searchRules(),
            'status' => ['nullable', 'in:active,archived'],
            'date' => $this->presentDateRules(),
        ];
    }

    protected function defaults(): array
    {
        return ['search' => '', 'status' => 'active', 'date' => ''];
    }
}