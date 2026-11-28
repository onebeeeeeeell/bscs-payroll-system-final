<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // letters, spaces, . ' - lang (pareho ng employee name), may kahit isang letter
            'name' => [
                'bail',
                'required',
                'string',
                'max:50',
                'regex:/^[\p{L}\p{M}.\'-]+(?:\s+[\p{L}\p{M}.\'-]+)*\z/u',
                'regex:/\p{L}/u',
            ],
            'email' => [
                'bail',
                'required',
                'string',
                'max:255',
                'email:rfc',
                // lowercase lang, para pareho ang unique check at ang sine-save ng service
                'lowercase',
                Rule::unique(User::class, 'email'),
            ],
            'password' => [
                'bail',
                'required',
                'string',
                'max:64',
                'confirmed',
                Password::defaults(),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'The name may only contain letters, spaces, hyphens, apostrophes, and periods, and must have at least one letter.',
            'email.unique' => 'This email is already registered.',
        ];
    }
}