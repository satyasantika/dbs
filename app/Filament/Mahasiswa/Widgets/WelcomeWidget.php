<?php

namespace App\Filament\Mahasiswa\Widgets;

use Filament\Widgets\Widget;

class WelcomeWidget extends Widget
{
    protected static bool $isLazy = false;

    protected static string $view = 'filament.mahasiswa.widgets.welcome-widget';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';
}
