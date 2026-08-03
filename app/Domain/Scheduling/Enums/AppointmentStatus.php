<?php

namespace App\Domain\Scheduling\Enums;

enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Agendado',
            self::Confirmed => 'Confirmado',
            self::InProgress => 'Em atendimento',
            self::Completed => 'Concluído',
            self::Cancelled => 'Cancelado',
            self::NoShow => 'Não compareceu',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, match ($this) {
            self::Scheduled => [self::Confirmed, self::InProgress, self::Completed, self::Cancelled, self::NoShow],
            self::Confirmed => [self::InProgress, self::Completed, self::Cancelled, self::NoShow],
            self::InProgress => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled, self::NoShow => [],
        }, true);
    }
}
