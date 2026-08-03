<?php

namespace App\Domain\Finance\Enums;

enum FinancialEntryType: string
{
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return $this === self::Income ? 'Receita' : 'Despesa';
    }
}
