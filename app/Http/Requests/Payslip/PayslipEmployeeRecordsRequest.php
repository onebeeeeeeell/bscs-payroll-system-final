<?php

namespace App\Http\Requests\Payslip;

use App\Http\Requests\ListFiltersRequest;

class PayslipEmployeeRecordsRequest extends ListFiltersRequest
{
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:active,archived'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    protected function defaults(): array
    {
        return [
            'status' => 'active',
            'date' => '',
        ];
    }
}