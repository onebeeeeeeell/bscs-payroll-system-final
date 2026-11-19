<?php

namespace App\Http\Requests\Payslip;

use App\Http\Requests\ListFiltersRequest;

class PayslipIndexRequest extends ListFiltersRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,archived'],
        ];
    }

    protected function defaults(): array
    {
        return ['search' => '', 'status' => 'active'];
    }
}