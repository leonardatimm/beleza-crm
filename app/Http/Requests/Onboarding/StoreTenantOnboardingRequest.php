<?php

namespace App\Http\Requests\Onboarding;

use App\Domain\Tenancy\Enums\BusinessType;
use App\Domain\Tenancy\Enums\DocumentType;
use App\Rules\Cnpj;
use App\Rules\Cpf;
use App\Rules\UniqueTenantDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->current_team_id === null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return self::rulesFor((string) $this->input('business_type'));
    }

    /** @return array<string, array<int, mixed>> */
    public static function rulesFor(string $businessType): array
    {
        $isIndividual = $businessType === BusinessType::Individual->value;
        $isCompany = $businessType === BusinessType::Company->value;

        return [
            'business_type' => ['required', Rule::enum(BusinessType::class)],
            'cpf' => [Rule::excludeIf(! $isIndividual), 'required', 'string', new Cpf, new UniqueTenantDocument(DocumentType::Cpf)],
            'professional_name' => [Rule::excludeIf(! $isIndividual), 'required', 'string', 'max:255'],
            'cnpj' => [Rule::excludeIf(! $isCompany), 'required', 'string', new Cnpj, new UniqueTenantDocument(DocumentType::Cnpj)],
            'legal_name' => [Rule::excludeIf(! $isCompany), 'required', 'string', 'max:255'],
            'trade_name' => [Rule::excludeIf(! $isCompany), 'required', 'string', 'max:255'],
            'phone' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

                if (! in_array(strlen($digits), [10, 11], true)) {
                    $fail('O campo telefone/WhatsApp deve conter DDD e um número válido.');
                }
            }],
            'responsible_cpf' => [Rule::excludeIf(! $isCompany), 'required', 'string', new Cpf],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return self::attributeNames();
    }

    /** @return array<string, string> */
    public static function attributeNames(): array
    {
        return [
            'business_type' => 'tipo de negócio',
            'cpf' => 'CPF',
            'professional_name' => 'nome do profissional',
            'cnpj' => 'CNPJ',
            'legal_name' => 'razão social',
            'trade_name' => 'nome fantasia',
            'phone' => 'telefone/WhatsApp',
            'responsible_cpf' => 'CPF do responsável',
        ];
    }
}
