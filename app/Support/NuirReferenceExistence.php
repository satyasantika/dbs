<?php

namespace App\Support;

use App\Models\NuirReference;
use Illuminate\Validation\ValidationException;

class NuirReferenceExistence
{
    /**
     * @return list<string>
     */
    public static function issues(NuirReference $reference): array
    {
        $issues = [];

        if (blank($reference->link_ojs)) {
            $issues[] = 'Link OJS wajib ada untuk memvalidasi eksistensi referensi.';
        } elseif (NuirExternalUrl::normalize($reference->link_ojs) === null) {
            $issues[] = 'Link OJS tidak valid.';
        }

        if (blank($reference->indexer_name)) {
            $issues[] = 'Nama indexer wajib diisi.';
        }

        if (blank($reference->link_index)) {
            $issues[] = 'Link index wajib ada untuk memvalidasi eksistensi referensi.';
        } elseif (NuirExternalUrl::normalize($reference->link_index) === null) {
            $issues[] = 'Link index tidak valid.';
        }

        return $issues;
    }

    public static function isVerifiable(NuirReference $reference): bool
    {
        return self::issues($reference) === [];
    }

    public static function assertVerifiable(NuirReference $reference): void
    {
        $issues = self::issues($reference);

        if ($issues !== []) {
            throw ValidationException::withMessages([
                'reference' => 'Referensi belum memenuhi syarat eksistensi: '.implode(' ', $issues),
            ]);
        }
    }
}
