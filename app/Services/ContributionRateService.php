<?php

namespace App\Services;

use App\Models\ContributionRate;
use App\Models\ContributionType;
use App\Services\Concerns\SanitizesText;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ContributionRateService
{
    use SanitizesText;

    // Ito lang ang fields na puwedeng pumasok galing sa labas. Hindi na pwedeng
    // ilipat ng contribution type ang existing na rate, kaya wala ito sa update.
    private const CREATE_FIELDS = ['contribution_type_id', 'calculation_type', 'rate'];

    private const UPDATE_FIELDS = ['calculation_type', 'rate'];

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
            ->search($this->sanitizeSearch($search))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }


    /*
     * LABEL: Current rate ids
     * Purpose: Ang pinakabagong rate ng bawat type ang ginagamit sa payroll.
     */
    public function currentRateIds(): \Illuminate\Support\Collection
    {
        return ContributionRate::query()->latestPerType()->pluck('id');
    }

    public function typeOptions(): Collection
    {
        return ContributionType::query()->forSelection()->get();
    }

    public function create(array $data): ContributionRate
    {
        $data = $this->sanitizeAmountFields(Arr::only($data, self::CREATE_FIELDS), ['rate']);
        $this->assertAmountsFilled($data, ['rate']);

        // transaction so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => ContributionRate::create($data)
        );
    }


    public function update(ContributionRate $contributionRate, array $data): ContributionRate
    {
        $data = $this->sanitizeAmountFields(Arr::only($data, self::UPDATE_FIELDS), ['rate']);
        $this->assertAmountsFilled($data, ['rate']);

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