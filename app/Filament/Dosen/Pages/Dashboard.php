<?php

namespace App\Filament\Dosen\Pages;

use App\Filament\Dosen\Widgets\DashboardSectionDivider;
use App\Filament\Dosen\Widgets\DosenNuirManajerWidget;
use App\Filament\Dosen\Widgets\DosenNuirPembimbingWidget;
use App\Filament\Dosen\Widgets\DosenNuirValidatorWidget;
use App\Filament\Dosen\Widgets\DosenStatsWidget;
use App\Filament\Dosen\Widgets\WelcomeWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard Dosen';

    public function getWidgets(): array
    {
        return [
            WelcomeWidget::class,
            DosenStatsWidget::class,
            DashboardSectionDivider::class,
            DosenNuirPembimbingWidget::class,
            DashboardSectionDivider::class,
            DosenNuirManajerWidget::class,
            DashboardSectionDivider::class,
            DosenNuirValidatorWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 1;
    }
}
