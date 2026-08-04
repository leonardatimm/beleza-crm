<?php

namespace App\Rules;

use App\Support\BrazilianPhone as BrazilianPhoneSupport;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class BrazilianPhone implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! BrazilianPhoneSupport::isValid($value)) {
            $fail('O campo :attribute deve conter DDD e 10 ou 11 dígitos.');
        }
    }
}
