<?php

namespace App\Domain\Finance\Enums;

enum FinancialEntryStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Paid => 'Pago',
            self::Cancelled => 'Cancelado',
            self::Reversed => 'Estornado',
        };
    }
}
