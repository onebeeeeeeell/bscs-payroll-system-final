<?php

namespace App\Http\Requests\Concerns;

/*
 * LABEL: Shared validation rules
 * Purpose: Isang lugar para sa mga rule na ginagamit ng maraming request:
 * search box, halaga (amount), at petsang "ngayon lang".
 */
trait ValidatesInput
{
    /**
     * ?search= : letters (kasama ang accents), spaces, at . ' - lang, hanggang
     * 30 characters. Pareho ng characters na pinapayagan sa pangalan ng employee
     * (EmployeeRequest), kaya mahahanap ang "O'Brien", "Anne-Marie" at "Jr.".
     * \z (hindi $) para ang trailing newline ay hindi makalusot. Ang % at _ ay
     * hindi pinapayagan dito at ine-escape pa rin ng Like::contains() sa model.
     *
     * @return list<string>
     */
    protected function searchRules(): array
    {
        return ['nullable', 'string', 'max:30', 'regex:/^[\p{L}\p{M}.\'\- ]*\z/u'];
    }

    /**
     * Halaga: numbers at decimal point lang, hanggang 2 decimal places
     * (decimal 10,2 sa database = hanggang 8 digits bago ang decimal).
     *
     * @return list<string>
     */
    protected function amountRules(): array
    {
        return ['required', 'numeric', 'regex:/^\d{1,8}(\.\d{1,2})?$/'];
    }

    /**
     * Petsa na dapat ay ngayong araw lang (walang nakaraan o hinaharap).
     *
     * @return list<string>
     */
    protected function presentDateRules(bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'date_format:Y-m-d', 'date_equals:today'];
    }
}