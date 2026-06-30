<?php

namespace App\Support;

use App\Models\NuirContentReview;
use App\Models\NuirSubmission;
use Illuminate\Validation\ValidationException;

class NuirRevisionGate
{
    /**
     * @return array<string, string>
     */
    public static function pendingRevisionErrors(NuirSubmission $submission): array
    {
        $errors = [];

        if ($submission->hasRejectedReferences()) {
            $orders = $submission->references()
                ->where('ref_approved', false)
                ->pluck('ref_order')
                ->implode(', ');

            $errors['references'] = "Perbaiki referensi ditolak (#{$orders}) sebelum melanjutkan.";
        }

        foreach ($submission->rejectedNuiFields() as $field) {
            $label = ucfirst($field);
            $errors[$field] = "Perbaiki {$label} yang diminta revisi sebelum melanjutkan.";
        }

        return $errors;
    }

    public static function assertRevisionComplete(NuirSubmission $submission): void
    {
        $errors = self::pendingRevisionErrors($submission);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public static function clearRejectedContentReviews(NuirSubmission $submission, string $field): void
    {
        NuirContentReview::query()
            ->where('nuir_submission_id', $submission->id)
            ->where('field', $field)
            ->where('approved', false)
            ->delete();
    }
}
