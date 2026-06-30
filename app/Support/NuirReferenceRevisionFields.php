<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class NuirReferenceRevisionFields
{
    public const LINK_OJS = 'link_ojs';

    public const INDEXER_NAME = 'indexer_name';

    public const LINK_INDEX = 'link_index';

    public const LINK_DRIVE = 'link_drive';

    public const QUOTE = 'quote';

    public const RELEVANCE = 'relevance';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::LINK_OJS => 'Link OJS',
            self::INDEXER_NAME => 'Nama Indexer',
            self::LINK_INDEX => 'Link Index',
            self::LINK_DRIVE => 'Link Drive',
            self::QUOTE => 'Kutipan',
            self::RELEVANCE => 'Relevansi',
        ];
    }

    /**
     * @param  list<string>|null  $fields
     * @return list<string>
     */
    public static function normalize(?array $fields): array
    {
        if ($fields === null || $fields === []) {
            return [];
        }

        $selected = array_flip(array_values(array_intersect($fields, array_keys(self::options()))));

        return array_values(array_filter(
            array_keys(self::options()),
            fn (string $field): bool => isset($selected[$field]),
        ));
    }

    /**
     * @param  list<string>|null  $fields
     * @return list<string>
     */
    public static function labels(?array $fields): array
    {
        $options = self::options();

        return array_values(array_map(
            fn (string $field): string => $options[$field] ?? $field,
            self::normalize($fields),
        ));
    }

    public static function labelsText(?array $fields): ?string
    {
        $labels = self::labels($fields);

        return $labels === [] ? null : implode(', ', $labels);
    }

    /**
     * @param  list<string>|null  $fields
     */
    public static function assertSelectedForRevision(?array $fields): void
    {
        if (self::normalize($fields) === []) {
            throw ValidationException::withMessages([
                'ref_revision_fields' => 'Pilih minimal satu bagian referensi yang perlu diperbaiki.',
            ]);
        }
    }
}
