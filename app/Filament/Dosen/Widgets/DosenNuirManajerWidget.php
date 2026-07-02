<?php

namespace App\Filament\Dosen\Widgets;

use App\Filament\NuirManajer\Widgets\ManajerNuirStatsWidget;

class DosenNuirManajerWidget extends ManajerNuirStatsWidget
{
    protected ?string $heading = 'Manajemen NUIR';

    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('manajer nuir') ?? false;
    }
}
