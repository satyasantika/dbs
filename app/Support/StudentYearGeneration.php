<?php

namespace App\Support;

class StudentYearGeneration
{
    /**
     * Resolves a student's angkatan (year_generation) straight from their
     * username/NIM — e.g. "232151001" → "2023" — instead of depending on a
     * guide_examiners row existing (that row is only created by the exam
     * registration import, which happens long after NUIR).
     */
    public static function resolve(?string $username): ?string
    {
        $username = (string) $username;

        if (preg_match('/^(20\d{2})/', $username, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^(\d{2})/', $username, $matches)) {
            return '20'.$matches[1];
        }

        return null;
    }
}
