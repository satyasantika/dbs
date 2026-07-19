<?php

namespace App\Providers\Filament;

use App\Filament\Informasi\Pages\RecapList;
use App\Support\FilamentBrand;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Panel PUBLIK — sengaja TANPA ->authMiddleware(), berbeda dari semua panel
 * lain di app/Providers/Filament/. Menggantikan tampilan (bukan rute)
 * information/recap-list/{generation}/{context} lama (publik, di luar
 * middleware auth — lihat routes/web.php) dengan versi card grid. Rute lama
 * dan App\DataTables\InformationPassRecapDataTable TETAP ada, tidak dihapus.
 */
class InformasiPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('informasi')
            ->path('informasi')
            ->login(false)
            ->brandName(fn () => FilamentBrand::withHomeIcon('Informasi Publik'))
            ->homeUrl(fn () => route('welcome'))
            ->colors([
                'primary' => Color::Sky,
            ])
            ->darkMode(false)
            ->navigation(false)
            ->maxContentWidth(MaxWidth::Full)
            ->discoverPages(in: app_path('Filament/Informasi/Pages'), for: 'App\\Filament\\Informasi\\Pages')
            ->pages([
                RecapList::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ]);
    }
}
