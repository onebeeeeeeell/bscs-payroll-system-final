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
     * Halaga (gross pay, rate, atbp.): numbers at isang decimal point lang ang
     * itinitira, tapos 2 decimal places (hal. "1,500.5abc" -> "1500.50").
     * Ang laman na walang numero (o may minus sign) ay ginagawang blangko, hindi
     * 0.00 at hindi positibo; ang assertAmountsFilled() ang tumatanggi roon.
     *
     * @param array<string, mixed> $data
     * @param list<string> $fields
     * @return array<string, mixed>
     */
    protected function sanitizeAmountFields(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (! isset($data[$field]) || ! is_scalar($data[$field])) {
                continue;
            }

            if (str_contains((string) $data[$field], '-')) {
                $data[$field] = '';

                continue;
            }

            $clean = preg_replace('/[^\d.]/', '', (string) $data[$field]) ?? '';
            // isang decimal point lang: ang mga sumunod na tuldok ay tinatanggal
            [$whole, $decimals] = array_pad(explode('.', $clean, 2), 2, '');
            $decimals = str_replace('.', '', $decimals);

            $data[$field] = ($whole === '' && $decimals === '')
                ? ''
                : (ltrim($whole, '0') ?: '0').'.'.substr($decimals.'00', 0, 2);
        }

        return $data;
    }

    /**
     * Itinatapon ang halagang blangko pagkatapos linisin (hindi tunay na numero).
     *
     * @param array<string, mixed> $data
     * @param list<string> $fields
     */
    protected function assertAmountsFilled(array $data, array $fields): void
    {
        $errors = [];

        foreach ($fields as $field) {
            if (($data[$field] ?? '') === '') {
                $errors[$field] = 'Enter a valid amount (numbers only, up to 2 decimal places).';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
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