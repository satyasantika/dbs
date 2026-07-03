<?php

namespace App\Filament\Mahasiswa\Concerns;

use App\Models\GuideExaminer;
use App\Models\NuirSetting;
use App\Support\StudentYearGeneration;

trait HidesNuirNavigationWhenInactive
{
    public static function shouldRegisterNavigation(): bool
    {
        if (! static::canAccess()) {
            return false;
        }

        $user = auth()->user();
        $guideExaminer = GuideExaminer::where('user_id', $user->id)->first();
        $yearGeneration = $guideExaminer?->year_generation ?? StudentYearGeneration::resolve($user->username);

        if (! $yearGeneration) {
            return false;
        }

        $setting = NuirSetting::where('year_generation', $yearGeneration)
            ->where('active', true)
            ->first();

        return $setting && in_array($setting->stage, [1, 2], true);
    }
}
