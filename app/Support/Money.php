<?php

namespace App\Support;

use InvalidArgumentException;

class Money
{
    public static function toMinorUnits(string|int|float $amount): int
    {
        $amount = (string) $amount;

        if (! preg_match('/^(-?)(\d+)(?:\.(\d{1,2}))?$/D', $amount, $matches)) {
            throw new InvalidArgumentException('The amount must have no more than two decimal places.');
        }

        $minorUnits = ((int) $matches[2] * 100) + (int) str_pad($matches[3] ?? '', 2, '0');

        return ($matches[1] ?? '') === '-' ? -$minorUnits : $minorUnits;
    }

    public static function format(int $minorUnits): string
    {
        $sign = $minorUnits < 0 ? '-' : '';
        $absolute = abs($minorUnits);

        return $sign.intdiv($absolute, 100).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }
}
