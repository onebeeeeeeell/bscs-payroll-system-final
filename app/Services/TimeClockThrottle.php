<?php

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/*
 * LABEL: Time clock throttle
 * Purpose: Proteksyon sa PIN guessing ng public time clock (6 digits lang ang PIN).
 * Dalawang limit, parehong bumibilang ng FAILED attempts lang:
 * - Bawat IP + pangalan: 5 sa loob ng 1 minuto (pigil sa mabilisang hula ng isang tao).
 * - Bawat pangalan lang, kahit anong IP: 10 sa loob ng 10 minuto (pigil sa taong
 *   nagpapalit-palit ng IP para sa kilalang pangalan).
 * Tradeoff: habang naka-lock ang pangalan, pati ang tamang PIN ay hindi tatanggapin
 * hanggang matapos ang paghihintay; may magagawang mag-lock ng pangalan ng iba nang
 * pansamantala, pero hindi siya makakapag-time-in/out gamit ang hula.
 */
class TimeClockThrottle
{
    public const IP_MAX_ATTEMPTS = 5;

    public const IP_DECAY_SECONDS = 60;

    public const NAME_MAX_ATTEMPTS = 10;

    public const NAME_DECAY_SECONDS = 600;

    /**
     * @throws ValidationException kapag naka-lock ang IP + pangalan o ang pangalan
     */
    public function ensureAllowed(string $ip, string $fullName): void
    {
        foreach ([
            [$this->ipKey($ip, $fullName), self::IP_MAX_ATTEMPTS],
            [$this->nameKey($fullName), self::NAME_MAX_ATTEMPTS],
        ] as [$key, $maxAttempts]) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

                throw ValidationException::withMessages([
                    'pin' => 'Too many unsuccessful attempts. Please wait '.$minutes.' '.Str::plural('minute', $minutes).' before trying again.',
                ]);
            }
        }
    }

    public function recordFailure(string $ip, string $fullName): void
    {
        RateLimiter::hit($this->ipKey($ip, $fullName), self::IP_DECAY_SECONDS);
        RateLimiter::hit($this->nameKey($fullName), self::NAME_DECAY_SECONDS);
    }

    private function ipKey(string $ip, string $fullName): string
    {
        return "time-clock:failed:{$ip}:{$this->nameHash($fullName)}";
    }

    private function nameKey(string $fullName): string
    {
        return "time-clock:failed-name:{$this->nameHash($fullName)}";
    }

    // squish + lowercase: "jane  DOE" at "Jane Doe" ay iisang pangalan para hindi malusutan
    private function nameHash(string $fullName): string
    {
        return hash('sha256', mb_strtolower(Str::squish($fullName)));
    }
}