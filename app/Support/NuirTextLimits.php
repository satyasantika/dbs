<?php

namespace App\Support;

use App\Models\NuirSetting;
use Illuminate\Validation\ValidationException;

class NuirTextLimits
{
    public static function wordCount(string $text): int
    {
        $trimmed = trim($text);

        if ($trimmed === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $trimmed, -1, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * @return array<string, string>
     */
    public static function nuiFieldErrors(array $data, NuirSetting $setting): array
    {
        $errors = [];

        foreach (['novelty', 'urgency', 'impact'] as $field) {
            $message = self::validateNuiField($data[$field] ?? '', $setting, $field);

            if ($message !== null) {
                $errors[$field] = $message;
            }
        }

        return $errors;
    }

    public static function validateNuiField(string $value, NuirSetting $setting, string $field): ?string
    {
        $minWords = $setting->{"min_words_{$field}"};
        $maxWords = $setting->{"max_words_{$field}"};
        $maxChars = $setting->{"max_chars_{$field}"};
        $words = self::wordCount($value);

        if ($minWords !== null && $words < $minWords) {
            return "Minimal {$minWords} kata untuk ".ucfirst($field).'.';
        }

        if ($maxWords !== null && $words > $maxWords) {
            return "Maksimal {$maxWords} kata untuk ".ucfirst($field).'.';
        }

        if ($maxWords === null && $maxChars !== null && mb_strlen($value) > $maxChars) {
            return "Maksimal {$maxChars} karakter untuk ".ucfirst($field).'.';
        }

        return null;
    }

    public static function assertNuiFields(array $data, NuirSetting $setting): void
    {
        $errors = self::nuiFieldErrors($data, $setting);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
