<?php

namespace App\Support;

use App\Models\ExamRegistration;
use App\Models\ExamScore;
use App\Models\User;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ExaminerSlotSelectOptions
{
    public const FIELDS = [
        'guide1_id',
        'guide2_id',
        'examiner1_id',
        'examiner2_id',
        'examiner3_id',
    ];

    public static function optionsFor(Get $get, string $field): array
    {
        $currentId = filled($get($field)) ? (int) $get($field) : null;

        return self::lecturerQuery(self::assignedIdsExcept($get, $field), $currentId)
            ->pluck('name', 'id')
            ->all();
    }

    public static function replacementOptions(ExamRegistration $record, ExamScore $score): Collection
    {
        return self::lecturerQuery(self::assignedIdsFromRegistration($record, (int) $score->user_id))
            ->where('id', '!=', $score->user_id)
            ->get(['id', 'name']);
    }

    /**
     * @return array<int, int>
     */
    public static function assignedIdsFromRegistration(ExamRegistration $record, ?int $exceptUserId = null): array
    {
        $ids = array_map('intval', array_filter([
            $record->examiner1_id,
            $record->examiner2_id,
            $record->examiner3_id,
            $record->guide1_id,
            $record->guide2_id,
        ]));

        if ($exceptUserId) {
            $ids = array_values(array_filter($ids, fn (int $id): bool => $id !== $exceptUserId));
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<int, int>
     */
    public static function assignedIdsExcept(Get $get, ?string $exceptField = null): array
    {
        $ids = [];

        foreach (self::FIELDS as $field) {
            if ($field === $exceptField) {
                continue;
            }

            $value = $get($field);

            if (filled($value)) {
                $ids[] = (int) $value;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<int, int>  $excludeIds
     * @return Builder<User>
     */
    protected static function lecturerQuery(array $excludeIds, ?int $alwaysIncludeId = null): Builder
    {
        return User::query()
            ->role('dosen')
            ->when(
                $excludeIds !== [] || $alwaysIncludeId,
                fn (Builder $query) => $query->where(function (Builder $query) use ($excludeIds, $alwaysIncludeId): void {
                    if ($excludeIds !== []) {
                        $query->whereNotIn('id', $excludeIds);
                    }

                    if ($alwaysIncludeId) {
                        $query->orWhere('id', $alwaysIncludeId);
                    }
                }),
            )
            ->orderBy('name');
    }
}
