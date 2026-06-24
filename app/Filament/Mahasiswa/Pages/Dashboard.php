<?php

namespace App\Filament\Mahasiswa\Pages;

use App\Filament\Concerns\AuthorizesMahasiswaPanelAccess;
use App\Filament\Mahasiswa\Widgets\MahasiswaNuirStatsWidget;
use App\Filament\Mahasiswa\Widgets\ShortcutsWidget;
use App\Filament\Mahasiswa\Widgets\WelcomeWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    use AuthorizesMahasiswaPanelAccess;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Dashboard Mahasiswa';

    protected static ?int $navigationSort = -2;

    public function getWidgets(): array
    {
        return [
            WelcomeWidget::class,
            MahasiswaNuirStatsWidget::class,
            ShortcutsWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 1;
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'mahasiswa', $tenant);
    }
}
