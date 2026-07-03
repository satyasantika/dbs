<?php

namespace App\Support;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class FilamentBrand
{
    public static function withHomeIcon(string $label): HtmlString
    {
        return new HtmlString(Blade::render(
            '<span class="flex items-center gap-x-2"><x-filament::icon icon="heroicon-o-home" class="h-5 w-5 shrink-0" />{{ $label }}</span>',
            ['label' => $label],
        ));
    }
}
