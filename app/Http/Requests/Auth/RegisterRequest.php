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
            // letters at single spaces lang (pareho ng department name), hanggang 30 characters
            'name' => [
                'bail',
                'required',
                'string',
                'max:30',
                'regex:/^[\p{L}]+(?:[ ][\p{L}]+)*$/u',
            ],
            'email' => [
                'bail',
                'required',
                'string',
                'max:30',
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
            'name.regex' => 'The name may only contain letters and single spaces (no numbers or special characters).',
            'email.unique' => 'This email is already registered.',
        ];
    }
}