<?php

namespace App\Rules;

use App\Support\BrazilianDocument;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class Cnpj implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! BrazilianDocument::isValidCnpj($value)) {
            $fail('O campo :attribute deve conter um CNPJ válido.');
        }
    }
}
