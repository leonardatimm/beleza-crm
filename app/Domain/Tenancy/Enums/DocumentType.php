<?php

namespace App\Domain\Tenancy\Enums;

enum DocumentType: string
{
    case Cpf = 'CPF';
    case Cnpj = 'CNPJ';
}
