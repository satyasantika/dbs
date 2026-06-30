<?php

namespace Tests\Concerns;

use App\Models\GuideAllocation;
use App\Models\User;

trait SeedsNuirGuideQuota
{
    protected function seedGuideAllocation(
        User $dosen,
        string $yearGeneration = '2022',
        int $guide1Quota = 2,
        int $guide2Quota = 2,
    ): GuideAllocation {
        return GuideAllocation::create([
            'user_id' => $dosen->id,
            'year' => (int) $yearGeneration,
            'guide1_quota' => $guide1Quota,
            'guide2_quota' => $guide2Quota,
            'guide1_filled' => 0,
            'guide2_filled' => 0,
            'active' => true,
        ]);
    }
}
