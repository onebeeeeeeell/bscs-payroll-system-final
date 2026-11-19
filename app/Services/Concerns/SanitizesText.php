<?php


namespace App\Services\Concerns;

use Illuminate\Support\Str;


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
}