<?php

namespace App\Support;

class UserPhone
{
    public static function normalize(?string $raw): string
    {
        $digits = preg_replace('/\D/', '', (string) $raw) ?? '';
        $digits = ltrim($digits, '0');

        if (str_starts_with($digits, '62') && strlen($digits) >= 11) {
            $digits = ltrim(substr($digits, 2), '0');
        }

        return $digits;
    }
}
