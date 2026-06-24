<?php

namespace App\Filament\NuirValidator\Pages;

use App\Filament\NuirValidator\Widgets\ValidatorNuirStatsWidget;
use App\Filament\NuirValidator\Widgets\WelcomeWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Dashboard Validator NUIR';

    public function getWidgets(): array
    {
        return [
            WelcomeWidget::class,
            ValidatorNuirStatsWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 1;
    }
}
