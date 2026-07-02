<?php

namespace App\Filament\Dosen\Widgets;

use App\Filament\NuirValidator\Widgets\ValidatorNuirStatsWidget;

class DosenNuirValidatorWidget extends ValidatorNuirStatsWidget
{
    protected ?string $heading = 'Validasi NUIR';

    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('validator nuir') ?? false;
    }
}
