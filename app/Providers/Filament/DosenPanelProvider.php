<?php

namespace App\Providers\Filament;

use App\Http\Middleware\FilamentAuthenticate as Authenticate;
use App\Filament\Dosen\Pages\ChiefExam;
use App\Filament\Dosen\Pages\Dashboard;
use App\Filament\Dosen\Pages\EditScoring;
use App\Filament\Dosen\Pages\GraduationEvidence;
use App\Filament\Dosen\Pages\GuideSupervision;
use App\Filament\Dosen\Pages\Scoring;
use App\Filament\Dosen\Pages\UnscoredScoring;
use App\Filament\Dosen\Pages\ViewChiefExam;
use App\Support\FilamentBrand;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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

class DosenPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('dosen')
            ->path('home')
            ->login(false)
            ->brandName(fn () => FilamentBrand::withHomeIcon('Portal Dosen'))
            ->homeUrl(fn () => route('home'))
            ->colors([
                'primary' => Color::Blue,
            ])
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            ->collapsedSidebarWidth('3.5rem')
            ->maxContentWidth(MaxWidth::Full)
            ->discoverPages(in: app_path('Filament/Dosen/Pages'), for: 'App\\Filament\\Dosen\\Pages')
            ->pages([
                Dashboard::class,
                \App\Filament\Shared\Pages\EditProfile::class,
                \App\Filament\Shared\Pages\ChangePassword::class,
                UnscoredScoring::class,
                Scoring::class,
                EditScoring::class,
                ChiefExam::class,
                ViewChiefExam::class,
                GuideSupervision::class,
                GraduationEvidence::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Dosen/Widgets'), for: 'App\\Filament\\Dosen\\Widgets')
            ->discoverResources(in: app_path('Filament/Dosen/Resources'), for: 'App\\Filament\\Dosen\\Resources')
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
