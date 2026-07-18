<?php

namespace App\Providers\Filament;

use App\Filament\NuirValidator\Pages\Dashboard;
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

class NuirValidatorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('nuir-validator')
            ->path('nuir-validator')
            ->login(false)
            ->brandName(fn () => FilamentBrand::withHomeIcon('Portal Validator NUIR'))
            ->homeUrl(fn () => route('home'))
            ->colors([
                'primary' => Color::Teal,
            ])
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            ->collapsedSidebarWidth('3.5rem')
            ->maxContentWidth(MaxWidth::Full)
            ->discoverResources(in: app_path('Filament/NuirValidator/Resources'), for: 'App\\Filament\\NuirValidator\\Resources')
            ->discoverPages(in: app_path('Filament/NuirValidator/Pages'), for: 'App\\Filament\\NuirValidator\\Pages')
            ->discoverWidgets(in: app_path('Filament/NuirValidator/Widgets'), for: 'App\\Filament\\NuirValidator\\Widgets')
            ->pages([
                Dashboard::class,
                \App\Filament\Shared\Pages\EditProfile::class,
                \App\Filament\Shared\Pages\ChangePassword::class,
            ])
            ->navigationGroups([
                // Tanpa icon di level grup — menghindari icon anggota grup
                // (NuirSubmissionResource/NuirReferenceResource) disembunyikan
                // oleh mode dropdown grup di Filament.
                NavigationGroup::make('Validasi NUIR'),
            ])
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => view('filament.shared.role-switcher')->render(),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn (): string => view('filament.shared.sidebar-footer')->render(),
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
