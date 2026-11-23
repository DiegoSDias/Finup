<?php

namespace App\Support;

use InvalidArgumentException;

class Money
{
    public static function cents(string|int|float $amount): int
    {
        if (is_float($amount)) {
            $amount = number_format($amount, 2, '.', '');
        }
        $value = (string) $amount;
        if (! preg_match('/^(-?)(\d+)(?:\.(\d{1,2}))?$/', $value, $matches)) {
            throw new InvalidArgumentException('Valor monetário inválido.');
        }
        $cents = (int) $matches[2] * 100 + (int) str_pad($matches[3] ?? '', 2, '0');
        return $matches[1] === '-' ? -$cents : $cents;
    }

    public static function decimal(int $cents): string
    {
        $absolute = abs($cents);
        return ($cents < 0 ? '-' : '').intdiv($absolute, 100).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }
}
