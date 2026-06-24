<?php

namespace App\Filament\Mahasiswa\Pages;

use App\Filament\Concerns\AuthorizesMahasiswaPanelAccess;
use Filament\Pages\Page;

class Dashboard extends Page
{
    use AuthorizesMahasiswaPanelAccess;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Dashboard Mahasiswa';

    protected static ?int $navigationSort = -2;

    protected static string $view = 'filament.mahasiswa.pages.dashboard';

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'mahasiswa', $tenant);
    }
}
