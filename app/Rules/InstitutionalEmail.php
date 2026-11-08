<?php

namespace App\Rules;

use App\Models\InstitutionDomain;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Schema;

class InstitutionalEmail implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = strtolower(trim((string) $value));
        $atPos = strrpos($email, '@');

        if ($atPos === false || $atPos === 0 || $atPos === strlen($email) - 1) {
            $fail('This email domain is not allowed. Please use your institution email.');
            return;
        }

        $domain = substr($email, $atPos + 1);

        if (! Schema::hasTable('institution_domains')) {
            $fallbackDomain = strtolower((string) config('institution.email_domain', 'citycollegeoftagaytay.edu.ph'));
            if ($domain !== $fallbackDomain) {
                $fail('This email domain is not allowed. Please use your institution email.');
            }
            return;
        }

        $isAllowed = InstitutionDomain::query()
            ->where('domain', $domain)
            ->where('is_active', true)
            ->exists();

        if (! $isAllowed) {
            $fail('This email domain is not allowed. Please use your institution email.');
        }
    }
}
