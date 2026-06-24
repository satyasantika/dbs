<?php

namespace App\Filament\Mahasiswa\Concerns;

use App\Models\GuideExaminer;
use App\Models\NuirSetting;

trait HidesNuirNavigationWhenInactive
{
    public static function shouldRegisterNavigation(): bool
    {
        if (! static::canAccess()) {
            return false;
        }

        $user = auth()->user();
        $guideExaminer = GuideExaminer::where('user_id', $user->id)->first();

        if (! $guideExaminer) {
            return false;
        }

        $setting = NuirSetting::where('year_generation', $guideExaminer->year_generation)
            ->where('active', true)
            ->first();

        return $setting && in_array($setting->stage, [1, 2], true);
    }
}
