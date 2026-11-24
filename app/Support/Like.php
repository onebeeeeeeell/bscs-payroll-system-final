<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class Like
{
    /*
     * LABEL: Case-insensitive operator
     * Purpose: PostgreSQL = ilike (kayang gumamit ng pg_trgm indexes). Ang ibang
     * database (SQLite sa tests, MySQL) ay walang ilike: like na lang, na
     * case-insensitive na doon. Dito lang dapat nakasulat ang pagpili.
     */
    public static function operator(Builder $query): string
    {
        return $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

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