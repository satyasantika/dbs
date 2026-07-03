<?php

namespace App\Providers\Filament;

use App\Filament\NuirManajer\Pages\Dashboard;
use App\Http\Middleware\FilamentAuthenticate as Authenticate;
use App\Support\FilamentBrand;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class NuirManajerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('nuir-manajer')
            ->path('nuir-manajer')
            ->login(false)
            ->brandName(fn () => FilamentBrand::withHomeIcon('Portal Manajer NUIR'))
            ->homeUrl(fn () => route('home'))
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth(MaxWidth::SevenExtraLarge)
            ->discoverResources(in: app_path('Filament/NuirManajer/Resources'), for: 'App\\Filament\\NuirManajer\\Resources')
            ->discoverPages(in: app_path('Filament/NuirManajer/Pages'), for: 'App\\Filament\\NuirManajer\\Pages')
            ->discoverWidgets(in: app_path('Filament/NuirManajer/Widgets'), for: 'App\\Filament\\NuirManajer\\Widgets')
            ->pages([
                Dashboard::class,
            ])
            ->navigationGroups([
                // Tanpa icon di level grup — menghindari icon anggota grup
                // (NuirSettingResource/NuirSubmissionResource/GuideAllocationResource)
                // disembunyikan oleh mode dropdown grup di Filament.
                NavigationGroup::make('Manajemen NUIR'),
            ])
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => view('filament.shared.role-switcher')->render(),
            )
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
