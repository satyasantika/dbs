<?php

namespace App\Services\Examination;

use App\Models\ExamRegistration;
use App\Models\ExamScore;
use App\Models\GuideExaminer;
use App\Models\User;
use Illuminate\Support\Collection;

class ExamRegistrationExaminerSync
{
    public const SLOT_FIELDS = [
        1 => 'examiner1_id',
        2 => 'examiner2_id',
        3 => 'examiner3_id',
        4 => 'guide1_id',
        5 => 'guide2_id',
    ];

    /**
     * @return array<int, int|null>
     */
    public function slotsFromRegistration(ExamRegistration $record): array
    {
        return [
            1 => $record->examiner1_id,
            2 => $record->examiner2_id,
            3 => $record->examiner3_id,
            4 => $record->guide1_id,
            5 => $record->guide2_id,
        ];
    }

    public function syncFromRegistration(ExamRegistration $record): void
    {
        $record->refresh();
        $record = $this->normalizeChiefIdOnRegistration($record);

        $this->syncGuideExaminer($record);
        $this->syncExamScores($record);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function resolveChiefIdForSave(ExamRegistration $record, array $data): array
    {
        return $this->resolveChiefIdForSaveFromSlots($this->slotsFromRegistration($record), $data);
    }

    /**
     * @param  array<int, int|null>  $oldSlots
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function resolveChiefIdForSaveFromSlots(array $oldSlots, array $data): array
    {
        $newSlots = [
            1 => $data['examiner1_id'] ?? null,
            2 => $data['examiner2_id'] ?? null,
            3 => $data['examiner3_id'] ?? null,
            4 => $data['guide1_id'] ?? null,
            5 => $data['guide2_id'] ?? null,
        ];

        $chiefId = filled($data['chief_id'] ?? null) ? (int) $data['chief_id'] : null;

        if (! $chiefId) {
            return $data;
        }

        $newSlotUserIds = array_map('intval', array_filter(array_values($newSlots)));

        if (in_array($chiefId, $newSlotUserIds, true)) {
            return $data;
        }

        foreach ($oldSlots as $order => $oldUserId) {
            if (! $oldUserId || (int) $oldUserId !== $chiefId) {
                continue;
            }

            $newUserId = $newSlots[$order] ?? null;

            if ($newUserId && (int) $newUserId !== $chiefId) {
                $data['chief_id'] = $newUserId;

                return $data;
            }

            $data['chief_id'] = null;

            return $data;
        }

        $data['chief_id'] = null;

        return $data;
    }

    /**
     * @return array<int, int|null>
     */
    public function slotsFromGuideExaminer(GuideExaminer $record): array
    {
        return [
            1 => $record->examiner1_id,
            2 => $record->examiner2_id,
            3 => $record->examiner3_id,
            4 => $record->guide1_id,
            5 => $record->guide2_id,
        ];
    }

    protected function normalizeChiefIdOnRegistration(ExamRegistration $record): ExamRegistration
    {
        $slotUserIds = array_map('intval', array_filter(array_values($this->slotsFromRegistration($record))));
        $chiefId = filled($record->chief_id) ? (int) $record->chief_id : null;

        if ($chiefId && ! in_array($chiefId, $slotUserIds, true)) {
            $record->update(['chief_id' => null]);

            return $record->fresh();
        }

        return $record;
    }

    public function replaceExaminer(ExamRegistration $record, ExamScore $score, int $newExaminerId): void
    {
        $oldExaminerId = (int) $score->user_id;
        $order = (int) $score->examiner_order;
        $field = self::SLOT_FIELDS[$order] ?? null;

        if (! $field) {
            throw new \InvalidArgumentException('Urutan penguji tidak valid.');
        }

        $score->update(['user_id' => $newExaminerId]);

        $updates = [$field => $newExaminerId];

        if ((int) $record->chief_id === $oldExaminerId) {
            $updates['chief_id'] = $newExaminerId;
        }

        $record->update($updates);

        $this->syncFromRegistration($record->fresh());
    }

    protected function syncExamScores(ExamRegistration $record): void
    {
        $filledSlots = array_filter($this->slotsFromRegistration($record));

        if ($filledSlots === []) {
            return;
        }

        $existingScores = ExamScore::query()
            ->where('exam_registration_id', $record->id)
            ->orderBy('examiner_order')
            ->get()
            ->keyBy('examiner_order');

        if ($this->hasCompleteExamScoreOrder($filledSlots, $existingScores)) {
            $this->updateExamScoresByOrder($record, $filledSlots, $existingScores);

            return;
        }

        $this->upsertExamScoresByUser($record, $filledSlots);
    }

    /**
     * @param  array<int, int|null>  $filledSlots
     */
    protected function hasCompleteExamScoreOrder(array $filledSlots, Collection $existingScores): bool
    {
        if ($existingScores->isEmpty()) {
            return false;
        }

        foreach (array_keys($filledSlots) as $order) {
            if (! $existingScores->has($order)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, int|null>  $filledSlots
     */
    protected function updateExamScoresByOrder(
        ExamRegistration $record,
        array $filledSlots,
        Collection $existingScores,
    ): void {
        $activeUserIds = [];

        foreach ($filledSlots as $order => $userId) {
            /** @var ExamScore $score */
            $score = $existingScores->get($order);

            if ((int) $score->user_id !== (int) $userId) {
                $score->update(['user_id' => $userId]);
            }

            $activeUserIds[] = $userId;
        }

        $this->deleteOrphanExamScores($record, $activeUserIds);
    }

    /**
     * @param  array<int, int|null>  $filledSlots
     */
    protected function upsertExamScoresByUser(ExamRegistration $record, array $filledSlots): void
    {
        $activeUserIds = [];

        foreach ($filledSlots as $order => $userId) {
            ExamScore::updateOrCreate(
                [
                    'exam_registration_id' => $record->id,
                    'user_id' => $userId,
                ],
                ['examiner_order' => $order]
            );

            $activeUserIds[] = $userId;
        }

        $this->deleteOrphanExamScores($record, $activeUserIds);
    }

    /**
     * @param  array<int, int|string|null>  $activeUserIds
     */
    protected function deleteOrphanExamScores(ExamRegistration $record, array $activeUserIds): void
    {
        ExamScore::query()
            ->where('exam_registration_id', $record->id)
            ->whereNotIn('user_id', $activeUserIds ?: [0])
            ->whereNull('grade')
            ->delete();
    }

    protected function syncGuideExaminer(ExamRegistration $record): void
    {
        if (! $record->user_id) {
            return;
        }

        $record->loadMissing('student');

        $attributes = [
            'guide1_id' => $record->guide1_id,
            'guide2_id' => $record->guide2_id,
            'examiner1_id' => $record->examiner1_id,
            'examiner2_id' => $record->examiner2_id,
            'examiner3_id' => $record->examiner3_id,
            'chief_id' => $record->chief_id,
        ];

        $examDateField = match ((int) $record->exam_type_id) {
            1 => 'proposal_date',
            2 => 'seminar_date',
            3 => 'thesis_date',
            default => null,
        };

        if ($examDateField && $record->exam_date) {
            $attributes[$examDateField] = $record->exam_date;
        }

        $guideExaminer = GuideExaminer::query()->firstOrNew([
            'user_id' => $record->user_id,
        ]);

        if (! $guideExaminer->exists) {
            $guideExaminer->year_generation = $this->resolveYearGeneration($record->student);
        }

        $guideExaminer->fill($attributes)->save();
    }

    protected function resolveYearGeneration(?User $student): string
    {
        $username = (string) ($student?->username ?? '');

        if (preg_match('/^(20\d{2})/', $username, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^(\d{2})/', $username, $matches)) {
            return '20'.$matches[1];
        }

        return (string) date('Y');
    }
}
