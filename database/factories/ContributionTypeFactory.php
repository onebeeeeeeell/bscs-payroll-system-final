<?php
namespace Database\Factories;

use App\Models\ContributionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContributionType>
 */
class ContributionTypeFactory extends Factory
{
    private const NAMES = ['SSS', 'PhilHealth', 'Pag-IBIG'];

    private static int $nameIndex = 0;

    /**
     * Define the model's default state.
     *
     * Walang magkaparehong name (may unique index na sa lower(name)), kaya ang
     * mga pangalan ay paikot, tapos may letter suffix kapag naubos na ang tatlo
     * (hal. "SSS b").
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $index = self::$nameIndex++;
        $round = intdiv($index, count(self::NAMES));
        $suffix = $round === 0 ? '' : ' '.strtr((string) $round, '0123456789', 'abcdefghij');

        return [
            'name' => self::NAMES[$index % count(self::NAMES)].$suffix,
        ];
    }
}