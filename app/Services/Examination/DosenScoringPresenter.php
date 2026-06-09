<?php

namespace App\Services\Examination;

use App\Models\ExamRegistration;
use App\Models\ExamScore;
use App\Models\ExamType;
use Illuminate\Database\Eloquent\Builder;

class DosenScoringPresenter
{
    /**
     * @return array<int, array{name: string, code: string|null, count: int, color: string}>
     */
    public static function examTypeRecapFromQuery(Builder $query): array
    {
        $recapQuery = clone $query;

        // Drop inherited row selects (e.g. exam_scores.*) so GROUP BY is valid under ONLY_FULL_GROUP_BY.
        $recapQuery->getQuery()->columns = null;
        $recapQuery->getQuery()->orders = null;
        $recapQuery->getQuery()->unionOrders = null;

        $counts = $recapQuery
            ->join('exam_types', 'exam_types.id', '=', 'exam_registrations.exam_type_id')
            ->select('exam_types.name as name', 'exam_types.code as code')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('exam_types.id', 'exam_types.name', 'exam_types.code')
            ->orderBy('exam_types.id')
            ->get();

        $types = $counts->map(fn ($row): array => [
            'name' => $row->name,
            'code' => $row->code,
            'count' => (int) $row->total,
            'color' => self::examTypeBadgeColor($row->name, $row->code),
        ])->all();

        $knownCodes = collect($types)->pluck('code')->filter()->all();
        $missingTypes = ExamType::query()
            ->when($knownCodes !== [], fn (Builder $query) => $query->whereNotIn('code', $knownCodes))
            ->orderBy('id')
            ->get(['name', 'code']);

        foreach ($missingTypes as $examType) {
            $types[] = [
                'name' => $examType->name,
                'code' => $examType->code,
                'count' => 0,
                'color' => self::examTypeBadgeColor($examType->name, $examType->code),
            ];
        }

        return $types;
    }

    public static function examTypeRecapTotal(array $types): int
    {
        return (int) collect($types)->sum('count');
    }

    public static function examTypeBadgeColor(?string $examTypeName, ?string $examTypeCode = null): string
    {
        $name = strtolower($examTypeName ?? '');
        $code = strtolower($examTypeCode ?? '');

        return match (true) {
            $code === 'sempro' || str_contains($name, 'proposal') => 'success',
            $code === 'semhas' || str_contains($name, 'hasil penelitian') || str_contains($name, 'semhas') => 'info',
            $code === 'skripsi' || str_contains($name, 'skripsi') => 'warning',
            default => 'gray',
        };
    }

    /**
     * @return array<int, array{order: int, name: string, is_chief: bool, is_current: bool, is_scored: bool}>
     */
    public static function examinersForRegistration(ExamRegistration $registration, ?int $highlightUserId = null): array
    {
        $registration->loadMissing(['examScores.lecture', 'chief']);

        $scores = $registration->examScores->sortBy('examiner_order');

        if ($scores->isNotEmpty()) {
            return $scores
                ->map(fn (ExamScore $score): array => [
                    'order' => (int) $score->examiner_order,
                    'name' => $score->lecture?->name ?? '(?)',
                    'is_chief' => (bool) ($registration->chief_id && $score->user_id === $registration->chief_id),
                    'is_current' => (bool) ($highlightUserId && $score->user_id === $highlightUserId),
                    'is_scored' => filled($score->grade),
                ])
                ->values()
                ->all();
        }

        return self::examinerSlotsFromRegistration($registration, $highlightUserId);
    }

    /**
     * @return array<int, array{order: int, name: string, is_chief: bool, is_current: bool, is_scored: bool}>
     */
    protected static function examinerSlotsFromRegistration(ExamRegistration $registration, ?int $highlightUserId = null): array
    {
        $registration->loadMissing([
            'examiner1:id,name',
            'examiner2:id,name',
            'examiner3:id,name',
            'guide1:id,name',
            'guide2:id,name',
        ]);

        $slots = collect([
            ['order' => 1, 'user_id' => $registration->examiner1_id, 'name' => $registration->examiner1?->name],
            ['order' => 2, 'user_id' => $registration->examiner2_id, 'name' => $registration->examiner2?->name],
            ['order' => 3, 'user_id' => $registration->examiner3_id, 'name' => $registration->examiner3?->name],
            ['order' => 4, 'user_id' => $registration->guide1_id, 'name' => $registration->guide1?->name],
            ['order' => 5, 'user_id' => $registration->guide2_id, 'name' => $registration->guide2?->name],
        ])->filter(fn (array $slot): bool => filled($slot['user_id']));

        return $slots
            ->map(fn (array $slot): array => [
                'order' => $slot['order'],
                'name' => $slot['name'] ?? '(?)',
                'is_chief' => (bool) ($registration->chief_id && $slot['user_id'] === $registration->chief_id),
                'is_current' => (bool) ($highlightUserId && $slot['user_id'] === $highlightUserId),
                'is_scored' => false,
            ])
            ->values()
            ->all();
    }
}
