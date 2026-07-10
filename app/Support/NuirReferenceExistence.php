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

        // Link Drive tidak wajib ada, tapi jika diisi harus berupa link yang valid.
        if (filled($reference->link_drive) && NuirExternalUrl::normalize($reference->link_drive) === null) {
            $issues[] = 'Link Drive tidak valid.';
        }

        return $issues;
    }

    public static function isVerifiable(NuirReference $reference): bool
    {
        return self::issues($reference) === [];
    }

    /**
     * Field link (ojs/index/drive) yang terisi tapi formatnya tidak valid —
     * dipakai tampilan validator/manajer untuk menandai form & menonaktifkan klik.
     *
     * @return array<string, bool>
     */
    public static function invalidLinkFields(NuirReference $reference): array
    {
        return [
            'link_ojs' => filled($reference->link_ojs) && NuirExternalUrl::normalize($reference->link_ojs) === null,
            'link_index' => filled($reference->link_index) && NuirExternalUrl::normalize($reference->link_index) === null,
            'link_drive' => filled($reference->link_drive) && NuirExternalUrl::normalize($reference->link_drive) === null,
        ];
    }

    public static function hasInvalidLink(NuirReference $reference): bool
    {
        return in_array(true, self::invalidLinkFields($reference), true);
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
