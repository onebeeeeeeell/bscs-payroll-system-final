<?php

namespace App\Services;

use App\Models\ContributionRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ContributionRateService
{
    /*
     * LABEL: Contribution rate list
     * Purpose: Nasa ContributionRate model na ang logic (eager load, search).
     * Dito, tinatawag na lang ang mga scopes.
     * withQueryString(): ini-keep ang ?search= sa pagination links.
     */
    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return ContributionRate::query()
            ->withType()
            ->withCount('deductions') // para malaman kung ginamit na sa payroll
            ->search($search)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }


    public function create(array $data): ContributionRate
    {
        // transaction so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => ContributionRate::create($data)
        );
    }


    public function update(ContributionRate $contributionRate, array $data): ContributionRate
    {
        return DB::transaction(function () use ($contributionRate, $data) {
            $this->assertUnused($contributionRate, 'rate');

            $contributionRate->update($data);

            return $contributionRate;
        });
    }


    public function delete(ContributionRate $contributionRate): void
    {
        DB::transaction(function () use ($contributionRate) {
            $this->assertUnused($contributionRate, 'rate');

            $contributionRate->delete();
        });
    }


    /*
     * LABEL: Used-rate guard
     * Purpose: Ang rate na may deductions na (ginamit na sa payroll) ay hindi na
     * binabago o binubura: ang pagbura ay mag-cascade sa deductions ng lumang
     * payrolls. Para magpalit ng halaga, mag-add ng BAGONG rate para sa type;
     * ang pinakabagong rate ang ginagamit sa susunod na payroll.
     */
    private function assertUnused(ContributionRate $contributionRate, string $errorKey): void
    {
        if ($contributionRate->deductions()->lockForUpdate()->exists()) {
            throw ValidationException::withMessages([
                $errorKey => 'This rate was already used in payroll. Add a new rate instead of changing it.',
            ]);
        }
    }

}