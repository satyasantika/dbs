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

    /**
     * Format tersimpan WAJIB tanpa awalan 0/62/+62 — nomor HP Indonesia
     * yang sudah dinormalisasi selalu diawali angka 8 (mis. 81234567890),
     * bukan 081234567890 atau 6281234567890. Dipakai setelah normalize()
     * untuk menolak input yang bukan nomor seluler Indonesia yang valid
     * (mis. nomor telepon rumah, kependekan, atau sampah non-angka).
     */
    public static function isValid(string $phone): bool
    {
        return (bool) preg_match('/^8[0-9]{7,13}$/', $phone);
    }
}
