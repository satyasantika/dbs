<?php

namespace App\Filament\Mahasiswa\Widgets;

use Filament\Widgets\Widget;

class ShortcutsWidget extends Widget
{
    protected static bool $isLazy = false;

    protected static string $view = 'filament.mahasiswa.widgets.shortcuts-widget';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';
}
