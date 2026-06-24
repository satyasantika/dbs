<?php

namespace App\Filament\NuirManajer\Widgets;

use Filament\Widgets\Widget;

class WelcomeWidget extends Widget
{
    protected static bool $isLazy = false;

    protected static string $view = 'filament.dosen.widgets.welcome-widget';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';
}
