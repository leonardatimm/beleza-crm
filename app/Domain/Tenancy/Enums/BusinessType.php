<?php

namespace App\Domain\Tenancy\Enums;

enum BusinessType: string
{
    case Individual = 'individual';
    case Company = 'company';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Pessoa Física',
            self::Company => 'Pessoa Jurídica',
        };
    }
}
