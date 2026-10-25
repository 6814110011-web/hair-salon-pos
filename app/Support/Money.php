<?php

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    public const MAX_CENTS = 9999999999;

    public static function cents(string|int $value): int
    {
        $value = (string) $value;
        if (! preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $value)) {
            throw new InvalidArgumentException('จำนวนเงินไม่ถูกต้อง');
        }
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $cents = (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
        if ($cents > self::MAX_CENTS) {
            throw new InvalidArgumentException('จำนวนเงินเกินขอบเขต');
        }

        return $cents;
    }

    public static function decimal(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        return $sign.intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
