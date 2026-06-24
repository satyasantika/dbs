<?php

namespace App\Filament\NuirManajer\Pages;

use App\Filament\NuirManajer\Widgets\ManajerNuirStatsWidget;
use App\Filament\NuirManajer\Widgets\WelcomeWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Dashboard Manajer NUIR';

    public function getWidgets(): array
    {
        return [
            WelcomeWidget::class,
            ManajerNuirStatsWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 1;
    }
}
