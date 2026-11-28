<?php

namespace App\Http\Requests\Review;

use App\Models\Review;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewRequest extends FormRequest
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
            // letters at single spaces lang, hanggang 30 characters (pareho ng register)
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
                Rule::unique(Review::class, 'email'),
            ],
            'rating' => [
                'bail',
                'required',
                'integer',
                'between:'.Review::MIN_RATING.','.Review::MAX_RATING,
            ],
            'comment' => [
                'bail',
                'required',
                'string',
                'min:5',
                'max:200',
            ],
            // Honeypot: nakatago sa form at walang laman ng tunay na tao; ang bot lang ang pupuno.
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'The name may only contain letters and single spaces (no numbers or special characters).',
            'email.unique' => 'This email has already submitted a review.',
            'rating.between' => 'Please choose a rating from 1 to 5 stars.',
            'rating.required' => 'Please choose a rating from 1 to 5 stars.',
            'website.prohibited' => 'Your review could not be submitted.',
        ];
    }
}