<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class ExamMenuWidget extends Widget
{
    protected static string $view = 'filament.widgets.exam-menu-widget';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';
}
