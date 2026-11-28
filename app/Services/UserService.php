<?php

namespace App\Services;

use App\Models\User;
use App\Services\Concerns\SanitizesText;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserService
{
    use SanitizesText;

    // Ito lang ang fields na puwedeng pumasok galing sa labas (hindi kasama ang password_confirmation).
    private const REGISTER_FIELDS = ['name', 'email', 'password'];

    // Free text lang ang nililinis dito. Ang password ay hindi ginagalaw (hashed na ng model cast).
    private const SANITIZED_TEXT_FIELDS = ['name', 'email'];

    /**
     * @param array<string, mixed> $data
     *
     * @throws ValidationException
     */
    public function register(array $data): User
    {
        $data = $this->sanitizeStringFields(Arr::only($data, self::REGISTER_FIELDS), self::SANITIZED_TEXT_FIELDS);
        $data['email'] = Str::lower($data['email']);

        try {
            return User::create($data);
        } catch (UniqueConstraintViolationException) {
            // Dalawang sabay na sign-up gamit ang parehong email: ang database ang huling bantay
            throw ValidationException::withMessages([
                'email' => 'This email is already registered.',
            ]);
        }
    }
}