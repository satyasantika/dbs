<?php

namespace App\Filament\Dosen\Widgets;

use Filament\Widgets\Widget;

class QuickLinksWidget extends Widget
{
    protected static string $view = 'filament.dosen.widgets.quick-links-widget';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';
}
