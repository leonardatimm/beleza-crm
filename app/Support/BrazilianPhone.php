<?php

namespace App\Support;

final class BrazilianPhone
{
    public static function digits(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    public static function isValid(mixed $value): bool
    {
        return in_array(strlen(self::digits($value)), [10, 11], true);
    }

    public static function format(mixed $value): string
    {
        $digits = self::digits($value);

        return match (strlen($digits)) {
            10 => preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $digits) ?? $digits,
            11 => preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $digits) ?? $digits,
            default => (string) $value,
        };
    }
}
