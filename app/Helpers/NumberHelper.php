<?php

namespace App\Helpers;

class NumberHelper
{
    public static function format(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        if (! is_numeric($value)) {
            return (string) $value;
        }

        $number = (float) $value;

        if (fmod($number, 1.0) === 0.0) {
            return number_format((int) $number, 0, ',', '.');
        }

        $formatted = number_format($number, 2, ',', '.');

        return rtrim(rtrim($formatted, '0'), ',');
    }
}
