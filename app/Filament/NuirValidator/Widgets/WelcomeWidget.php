<?php

namespace App\Filament\NuirValidator\Widgets;

use Filament\Widgets\Widget;

class WelcomeWidget extends Widget
{
    protected static bool $isLazy = false;

    protected static string $view = 'filament.nuir-validator.widgets.welcome-widget';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';
}
