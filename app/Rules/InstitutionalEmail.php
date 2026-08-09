<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class InstitutionalEmail implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $domain = config('institution.email_domain');
        if (! str_ends_with(strtolower($value), '@' . strtolower($domain))) {
            $fail('The :attribute must be a valid institutional email ending in @' . $domain . '.');
        }
    }
}
