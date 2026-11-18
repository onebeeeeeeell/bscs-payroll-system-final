<?php

namespace App\Support;

class Like
{
    /*
     * LABEL: Escape LIKE wildcards
     * Purpose: I-escape ang \, % at _ para literal ang hanap ng user.
     * Kung hindi, ang "_" lang na search ay magma-match sa LAHAT ng row.
     */
    public static function escape(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /*
     * LABEL: Contains pattern
     * Purpose: "%term%" na escaped na, handa nang i-bind sa LIKE / ILIKE.
     */
    public static function contains(string $value): string
    {
        return '%'.self::escape($value).'%';
    }
}