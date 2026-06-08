<?php

namespace App\Providers\Filament;

use App\Http\Middleware\FilamentAuthenticate as Authenticate;
use App\Filament\Dosen\Pages\Dashboard;
use App\Filament\Dosen\Pages\Scoring;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DosenPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('dosen')
            ->path('home')
            ->login(false)
            ->navigation(false)
            ->brandName('DBS Penguji')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->darkMode(false)
            ->discoverPages(in: app_path('Filament/Dosen/Pages'), for: 'App\\Filament\\Dosen\\Pages')
            ->pages([
                Dashboard::class,
                Scoring::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Dosen/Widgets'), for: 'App\\Filament\\Dosen\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
