<?php

namespace App\Filament\Dosen\Concerns;

use App\Models\GuideExaminer;

trait HasGuideSupervisionRecap
{
    /**
     * @return array<int, array{
     *     angkatan: mixed,
     *     total: int,
     *     pembimbing1: int,
     *     pembimbing2: int,
     *     belum_sempro: int,
     *     baru_sempro: int,
     *     sudah_semhas: int
     * }>
     */
    public function getGuideRecapByGeneration(): array
    {
        $userId = auth()->id();

        return GuideExaminer::query()
            ->select('year_generation')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN guide1_id = ? THEN 1 ELSE 0 END) as pembimbing1', [$userId])
            ->selectRaw('SUM(CASE WHEN guide2_id = ? THEN 1 ELSE 0 END) as pembimbing2', [$userId])
            ->selectRaw('SUM(CASE WHEN proposal_date IS NULL AND seminar_date IS NULL AND thesis_date IS NULL THEN 1 ELSE 0 END) as belum_sempro')
            ->selectRaw('SUM(CASE WHEN proposal_date IS NOT NULL AND seminar_date IS NULL AND thesis_date IS NULL THEN 1 ELSE 0 END) as baru_sempro')
            ->selectRaw('SUM(CASE WHEN seminar_date IS NOT NULL AND thesis_date IS NULL THEN 1 ELSE 0 END) as sudah_semhas')
            ->where(function ($q) use ($userId) {
                $q->where('guide1_id', $userId)
                    ->orWhere('guide2_id', $userId);
            })
            ->whereNull('thesis_date')
            ->groupBy('year_generation')
            ->orderBy('year_generation')
            ->get()
            ->map(fn ($row) => [
                'angkatan' => $row->year_generation,
                'total' => (int) $row->total,
                'pembimbing1' => (int) $row->pembimbing1,
                'pembimbing2' => (int) $row->pembimbing2,
                'belum_sempro' => (int) $row->belum_sempro,
                'baru_sempro' => (int) $row->baru_sempro,
                'sudah_semhas' => (int) $row->sudah_semhas,
            ])
            ->all();
    }
}
