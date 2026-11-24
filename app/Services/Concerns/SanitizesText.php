<?php


namespace App\Services\Concerns;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;


trait SanitizesText
{
    /**
     * Text lang ang nililinis dito (strip tags + squish). Huwag nang isama ang
     * IDs, amounts, at dates: validation sa FormRequest na ang bahala sa kanila.
     *
     * @param array<string, mixed> $data
     * @param list<string> $fields
     * @return array<string, mixed>
     */
    protected function sanitizeStringFields(array $data, array $fields, bool $stripTags = true): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $value = Str::of($data[$field]);


                if ($stripTags) {
                    $value = $value->stripTags();
                }


                // squish() = trim + isang space na lang ang sunod-sunod na spaces
                $data[$field] = $value->squish()->toString();
            }
        }


        return $data;
    }

    /*
     * Para sa ?search= ng mga index page: strip tags + squish. Ang % at _ ay
     * ine-escape naman ng Like::contains() sa model.
     */
    protected function sanitizeSearch(?string $search): string
    {
        return $this->sanitizeStringFields(['search' => $search], ['search'])['search'] ?? '';
    }

    /*
     * Halaga (gross pay, rate, atbp.): hindi inaayos ang maling laman, tinatanggihan.
     * Ang "abc12" ay hindi ginagawang 12.00 at ang "-100" ay hindi ginagawang 100.00:
     * pera ito, kaya mas ligtas ang error kaysa sa hulaan. Ang tama lang ang
     * ino-normalize: numbers lang, hanggang 2 decimal places, tapos laging 2 decimal
     * places ang itatabi (hal. 1500.5 -> "1500.50", " 15 " -> "15.00").
     * Pareho ng rule ng amountRules() sa FormRequest, kaya sa HTTP ay hindi ito aabot
     * dito nang mali; ito ang bantay kapag direktang tinawag ang service.
     *
     * @param array<string, mixed> $data
     * @param list<string> $fields
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected function normalizeAmounts(array $data, array $fields): array
    {
        $errors = [];

        foreach ($fields as $field) {
            $value = $data[$field] ?? null;

            if (is_int($value) || (is_float($value) && is_finite($value))) {
                $value = $value >= 0 ? number_format($value, 2, '.', '') : null;
            } elseif (is_string($value) && preg_match('/^\d+(?:\.\d{1,2})?$/', trim($value)) === 1) {
                [$whole, $decimals] = array_pad(explode('.', trim($value), 2), 2, '');
                $value = (ltrim($whole, '0') ?: '0').'.'.str_pad($decimals, 2, '0');
            } else {
                $value = null;
            }

            if ($value === null) {
                $errors[$field] = 'Enter a valid amount (numbers only, up to 2 decimal places).';

                continue;
            }

            $data[$field] = $value;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $data;
    }

    /*
     * Petsa (Y-m-d) lang ang pinapasa; kapag hindi tunay na petsa ay blangko.
     * Ang "ngayon lang" na rule ay nasa FormRequest.
     */
    protected function sanitizeDate(?string $date): string
    {
        $date = trim((string) $date);
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date ? $date : '';
    }

    /*
     * Para sa mga list filters: nililinis ang search, at ang date kung mayroon.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    protected function sanitizeFilters(array $filters): array
    {
        $filters['search'] = $this->sanitizeSearch($filters['search'] ?? null);

        if (array_key_exists('date', $filters)) {
            $filters['date'] = $this->sanitizeDate($filters['date']);
        }

        return $filters;
    }
}