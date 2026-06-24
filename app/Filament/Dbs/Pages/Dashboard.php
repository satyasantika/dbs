<?php

namespace App\Filament\Dbs\Pages;

use App\Filament\Widgets\ExamRegistrationsByDateWidget;
use App\Filament\Widgets\ExamStatsWidget;
use App\Filament\Widgets\UnscoredExamsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Dashboard DBS';

    public function getWidgets(): array
    {
        return [
            ExamStatsWidget::class,
            ExamRegistrationsByDateWidget::class,
            UnscoredExamsWidget::class,
        ];
    }
}
