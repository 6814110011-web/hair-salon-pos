<?php

namespace App\Support;

final class FormValue
{
    public static function old(string $key, mixed $default = ''): string
    {
        $value = old($key, $default);

        return is_scalar($value) ? (string) $value : '';
    }
}
