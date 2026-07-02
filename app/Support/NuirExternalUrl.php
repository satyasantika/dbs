<?php

namespace App\Support;

class NuirExternalUrl
{
    public static function normalize(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $url = trim($url);

        if (! preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://'.$url;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || ! str_contains($host, '.')) {
            return null;
        }

        return $url;
    }

    public static function isGoogleDrive(?string $url): bool
    {
        $normalized = self::normalize($url);

        if ($normalized === null) {
            return false;
        }

        return (bool) preg_match('/^https?:\/\/(drive\.google\.com|docs\.google\.com)\//i', $normalized);
    }

    public static function normalizeGoogleDrive(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $normalized = self::normalize($url);

        if ($normalized === null || ! self::isGoogleDrive($normalized)) {
            return null;
        }

        return $normalized;
    }
}
