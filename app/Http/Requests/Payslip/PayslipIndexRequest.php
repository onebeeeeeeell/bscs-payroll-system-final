<?php

namespace App\Http\Requests\Payslip;

use App\Http\Requests\ListFiltersRequest;

class PayslipIndexRequest extends ListFiltersRequest
{
    public function rules(): array
    {
        return [
            'search' => $this->searchRules(),
            'status' => ['nullable', 'in:active,archived'],
            'date' => $this->presentDateRules(),
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
        ];
    }

    protected function defaults(): array
    {
        return [
            'search' => '',
            'status' => 'active',
            'date' => '',
            'department_id' => '',
            'position_id' => '',
        ];
    }
}