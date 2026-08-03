<?php

namespace App\Domain\Tenancy\Data;

use App\Domain\Tenancy\Enums\BusinessType;

final readonly class CreateTenantData
{
    public function __construct(
        public BusinessType $businessType,
        public string $phone,
        public ?string $cpf = null,
        public ?string $professionalName = null,
        public ?string $cnpj = null,
        public ?string $legalName = null,
        public ?string $tradeName = null,
        public ?string $responsibleCpf = null,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            businessType: BusinessType::from($validated['business_type']),
            phone: $validated['phone'],
            cpf: $validated['cpf'] ?? null,
            professionalName: $validated['professional_name'] ?? null,
            cnpj: $validated['cnpj'] ?? null,
            legalName: $validated['legal_name'] ?? null,
            tradeName: $validated['trade_name'] ?? null,
            responsibleCpf: $validated['responsible_cpf'] ?? null,
        );
    }
}
