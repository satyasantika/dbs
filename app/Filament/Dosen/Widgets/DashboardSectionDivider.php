<?php

namespace App\Filament\Dosen\Widgets;

use Filament\Widgets\Widget;

class DashboardSectionDivider extends Widget
{
    protected static bool $isLazy = false;

    protected static string $view = 'filament.dosen.widgets.dashboard-section-divider';

    protected int | string | array $columnSpan = 'full';
}
