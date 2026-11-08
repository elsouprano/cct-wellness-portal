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
        $domain = strtolower((string) config('app.institutional_email_domain', config('institution.email_domain', 'citycollegeoftagaytay.edu.ph')));
        $email = strtolower(trim((string) $value));

        if (! str_ends_with($email, '@' . $domain)) {
            $fail('This email domain is not allowed. Please use your institution email.');
        }
    }
}
