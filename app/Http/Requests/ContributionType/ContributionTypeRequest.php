<?php

namespace App\Http\Requests\ContributionType;

use App\Models\ContributionType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ContributionTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /*
     * LABEL: Rules
     * Purpose: Name ng contribution type: letters lang, isang space sa pagitan ng
     * words (pareho ng Department/Position, para tugma ang unique check sa
     * sine-save ng service), at walang kapareho (hindi sensitive sa laki ng letra:
     * "sss" at "SSS" ay iisa). Ang update ay hindi nagko-conflict sa sarili niya.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $required = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'name' => [
                $required,
                'string',
                'max:30',
                'regex:/^[\p{L}]+(?:[ ][\p{L}]+)*$/u',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $current = $this->route('contribution_type');

                    $taken = ContributionType::query()
                        ->named((string) $value)
                        ->when($current instanceof ContributionType, fn ($query) => $query->whereKeyNot($current->getKey()))
                        ->exists();

                    if ($taken) {
                        $fail('A contribution type with this name already exists.');
                    }
                },
            ],
        ];
    }
}