<?php

namespace App\Http\Requests\Review;

use App\Http\Requests\ListFiltersRequest;

class ReviewListRequest extends ListFiltersRequest
{
    public const STATUSES = ['pending', 'approved', 'archived'];

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            // 'string' ang humaharang sa ?status[]=x
            'status' => ['nullable', 'string', 'in:'.implode(',', self::STATUSES)],
        ];
    }

    protected function defaults(): array
    {
        return ['status' => 'pending'];
    }
}