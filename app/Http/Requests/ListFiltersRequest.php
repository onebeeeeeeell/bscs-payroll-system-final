<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/*
 * LABEL: List filters base request
 * Purpose: Para sa mga index page na may filters (search, status, dates...).
 * Ang bawat child ay may rules() at defaults() lang. Ginagamit din ito sa
 * archive/restore para maibalik ang filters pagkatapos ng action.
 */
abstract class ListFiltersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rule ng ?search= sa mga page na pangalan/status ang hinahanap:
     * letters at spaces lang, hanggang 30 characters.
     *
     * @return list<string>
     */
    protected function searchRules(): array
    {
        return ['nullable', 'string', 'max:30', 'regex:/^[\pL ]*$/u'];
    }

    /**
     * Lahat ng filter key at ang default nito (string).
     *
     * @return array<string, string>
     */
    abstract protected function defaults(): array;

    /**
     * Para sa page: laging kumpleto ang keys.
     *
     * @return array<string, string>
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return collect($this->defaults())
            ->map(fn (string $default, string $key) => (string) ($validated[$key] ?? $default))
            ->all();
    }

    /**
     * Para sa redirect/URL: walang blanko at walang default na status.
     *
     * @return array<string, string>
     */
    public function redirectFilters(): array
    {
        return array_filter(
            $this->filters(),
            fn (string $value, string $key) => $value !== '' && ! ($key === 'status' && $value === 'active'),
            ARRAY_FILTER_USE_BOTH,
        );
    }
}