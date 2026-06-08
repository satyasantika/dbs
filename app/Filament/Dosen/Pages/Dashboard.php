<?php

namespace App\Filament\Dosen\Pages;

use App\Filament\Dosen\Widgets\DosenStatsWidget;
use App\Filament\Dosen\Widgets\QuickLinksWidget;
use App\Filament\Dosen\Widgets\WelcomeWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard Penguji';

    public function getWidgets(): array
    {
        return [
            WelcomeWidget::class,
            DosenStatsWidget::class,
            QuickLinksWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 1;
    }
}
