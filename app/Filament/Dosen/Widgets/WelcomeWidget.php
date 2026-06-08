<?php

namespace App\Filament\Dosen\Widgets;

use Filament\Widgets\Widget;

class WelcomeWidget extends Widget
{
    protected static string $view = 'filament.dosen.widgets.welcome-widget';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';
}
