<?php


namespace App\Services\Concerns;

use Illuminate\Support\Str;


trait SanitizesText
{
    /**
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


                $data[$field] = $value->trim()->toString();
            }
        }


        return $data;
    }
}