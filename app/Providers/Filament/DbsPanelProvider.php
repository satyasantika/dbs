<?php

namespace App\Providers\Filament;

use App\Filament\Dbs\Pages\Dashboard;
use App\Filament\Resources\ExamRegistrationResource;
use App\Filament\Resources\GuideAllocationResource;
use App\Filament\Resources\GuideExaminerResource;
use App\Filament\Resources\GuideGroupResource;
use App\Filament\Resources\ReadyExamResultsResource;
use App\Filament\Resources\SelectionElementCommentResource;
use App\Filament\Resources\SelectionElementResource;
use App\Filament\Resources\SetScoringToExaminerResource;
use App\Http\Middleware\FilamentAuthenticate as Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
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

class DbsPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('dbs')
            ->path('dbs')
            ->login(false)
            ->brandName('DBS Panel')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth(MaxWidth::SevenExtraLarge)
            ->discoverResources(in: app_path('Filament/Dbs/Resources'), for: 'App\\Filament\\Dbs\\Resources')
            ->resources([
                GuideAllocationResource::class,
                GuideGroupResource::class,
                SelectionElementResource::class,
                SelectionElementCommentResource::class,
                ExamRegistrationResource::class,
                GuideExaminerResource::class,
                SetScoringToExaminerResource::class,
                ReadyExamResultsResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Dbs/Pages'), for: 'App\\Filament\\Dbs\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->navigationGroups([
                NavigationGroup::make('Manajemen Seleksi')->icon('heroicon-o-funnel'),
                NavigationGroup::make('Manajemen NUIR')->icon('heroicon-o-document-text'),
                NavigationGroup::make('Manajemen Ujian')
                    ->icon('heroicon-o-academic-cap')
                    ->collapsed(),
            ])
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
