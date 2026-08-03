<?php

namespace App\Support;

final class BrazilianDocument
{
    public static function digits(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    public static function isValidCpf(mixed $value): bool
    {
        $digits = self::digits($value);

        if (strlen($digits) !== 11 || preg_match('/^(\d)\1{10}$/', $digits)) {
            return false;
        }

        for ($position = 9; $position <= 10; $position++) {
            $sum = 0;

            for ($index = 0; $index < $position; $index++) {
                $sum += (int) $digits[$index] * (($position + 1) - $index);
            }

            $digit = 11 - ($sum % 11);
            $digit = $digit >= 10 ? 0 : $digit;

            if ((int) $digits[$position] !== $digit) {
                return false;
            }
        }

        return true;
    }

    public static function isValidCnpj(mixed $value): bool
    {
        $digits = self::digits($value);

        if (strlen($digits) !== 14 || preg_match('/^(\d)\1{13}$/', $digits)) {
            return false;
        }

        $firstWeights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $secondWeights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        return self::cnpjDigit(substr($digits, 0, 12), $firstWeights) === (int) $digits[12]
            && self::cnpjDigit(substr($digits, 0, 13), $secondWeights) === (int) $digits[13];
    }

    /** @param array<int, int> $weights */
    private static function cnpjDigit(string $digits, array $weights): int
    {
        $sum = 0;

        foreach ($weights as $index => $weight) {
            $sum += (int) $digits[$index] * $weight;
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
