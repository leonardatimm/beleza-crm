<?php

namespace App\Rules;

use App\Domain\Tenancy\Enums\DocumentType;
use App\Models\Tenant;
use App\Support\BrazilianDocument;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class UniqueTenantDocument implements ValidationRule
{
    public function __construct(private readonly DocumentType $type) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = Tenant::query()
            ->where('document_type', $this->type->value)
            ->where('document_number', BrazilianDocument::digits($value))
            ->exists();

        if ($exists) {
            $fail('Já existe um estabelecimento cadastrado com este :attribute.');
        }
    }
}
