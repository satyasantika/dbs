<?php

namespace App\Services;

use App\Models\GuideAllocation;
use App\Models\NuirProposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NuirGuideQuotaService
{
    public function allocationFor(User $lecturer, string $yearGeneration): ?GuideAllocation
    {
        return GuideAllocation::query()
            ->where('user_id', $lecturer->id)
            ->where('year', (int) $yearGeneration)
            ->where('active', true)
            ->first();
    }

    public function remainingQuota(User $lecturer, int $guideOrder, string $yearGeneration): int
    {
        $allocation = $this->allocationFor($lecturer, $yearGeneration);

        if (! $allocation) {
            return 0;
        }

        if ($guideOrder === 1) {
            return max(0, $allocation->guide1_quota - $allocation->guide1_filled);
        }

        return max(0, $allocation->guide2_quota - $allocation->guide2_filled);
    }

    public function hasQuota(User $lecturer, int $guideOrder, string $yearGeneration): bool
    {
        return $this->remainingQuota($lecturer, $guideOrder, $yearGeneration) > 0;
    }

    public function consume(User $lecturer, int $guideOrder, string $yearGeneration): void
    {
        DB::transaction(function () use ($lecturer, $guideOrder, $yearGeneration) {
            $allocation = GuideAllocation::query()
                ->where('user_id', $lecturer->id)
                ->where('year', (int) $yearGeneration)
                ->where('active', true)
                ->lockForUpdate()
                ->first();

            $field = $guideOrder === 1 ? 'guide1_filled' : 'guide2_filled';
            $quotaField = $guideOrder === 1 ? 'guide1_quota' : 'guide2_quota';

            if (! $allocation || $allocation->{$field} >= $allocation->{$quotaField}) {
                throw ValidationException::withMessages([
                    $guideOrder === 1 ? 'guide1_id' : 'guide2_id' => 'Kuota pembimbing pada posisi ini sudah habis.',
                ]);
            }

            $allocation->increment($field);
        });
    }

    public function release(User $lecturer, int $guideOrder, string $yearGeneration): void
    {
        DB::transaction(function () use ($lecturer, $guideOrder, $yearGeneration) {
            $allocation = GuideAllocation::query()
                ->where('user_id', $lecturer->id)
                ->where('year', (int) $yearGeneration)
                ->where('active', true)
                ->lockForUpdate()
                ->first();

            if (! $allocation) {
                return;
            }

            $field = $guideOrder === 1 ? 'guide1_filled' : 'guide2_filled';

            if ($allocation->{$field} > 0) {
                $allocation->decrement($field);
            }
        });
    }

    /**
     * Hitung ulang guide1_filled/guide2_filled berdasarkan jumlah usulan
     * pending/accepted yang benar-benar aktif untuk angkatan ini, lalu
     * simpan jika berbeda dari nilai tersimpan. Dipakai untuk memulihkan
     * data bila terjadi kegagalan konsumsi/pelepasan kuota.
     *
     * @return int jumlah baris GuideAllocation yang dikoreksi
     */
    public function reconcile(string $yearGeneration): int
    {
        return DB::transaction(function () use ($yearGeneration) {
            $corrected = 0;

            $allocations = GuideAllocation::query()
                ->where('year', (int) $yearGeneration)
                ->where('active', true)
                ->lockForUpdate()
                ->get();

            foreach ($allocations as $allocation) {
                $actualGuide1Filled = NuirProposal::where('guide1_id', $allocation->user_id)
                    ->whereIn('guide1_status', ['pending', 'accepted'])
                    ->whereHas('submission', fn ($q) => $q->where('year_generation', $yearGeneration))
                    ->count();

                $actualGuide2Filled = NuirProposal::where('guide2_id', $allocation->user_id)
                    ->whereIn('guide2_status', ['pending', 'accepted'])
                    ->whereHas('submission', fn ($q) => $q->where('year_generation', $yearGeneration))
                    ->count();

                if ($allocation->guide1_filled !== $actualGuide1Filled || $allocation->guide2_filled !== $actualGuide2Filled) {
                    $allocation->update([
                        'guide1_filled' => $actualGuide1Filled,
                        'guide2_filled' => $actualGuide2Filled,
                    ]);
                    $corrected++;
                }
            }

            return $corrected;
        });
    }
}
