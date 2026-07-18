<?php

namespace App\Providers\Filament;

use App\Filament\Resources\ExamRegistrationResource;
use App\Filament\Resources\ExamTypeResource;
use App\Filament\Resources\GuideExaminerResource;
use App\Filament\Resources\ReadyExamResultsResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\SetScoringToExaminerResource;
use App\Filament\Resources\UserResource;
use App\Http\Middleware\FilamentAuthenticate as Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Portal Admin')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            ->collapsedSidebarWidth('3.5rem')
            ->maxContentWidth(MaxWidth::Full)
            ->resources([
                // Manajemen Seleksi & Permission sengaja tidak ditampilkan di panel
                // admin — Manajemen Seleksi sudah dikelola lewat panel Manajer NUIR.
                UserResource::class,
                RoleResource::class,
                ExamTypeResource::class,
                ExamRegistrationResource::class,
                GuideExaminerResource::class,
                SetScoringToExaminerResource::class,
                ReadyExamResultsResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\Shared\Pages\EditProfile::class,
                \App\Filament\Shared\Pages\ChangePassword::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Widgets\ExamStatsWidget::class,
                \App\Filament\Widgets\ExamRegistrationsByDateWidget::class,
            ])
            ->navigationGroups([
                // Sengaja tanpa ->icon(): grup dengan icon membuat Filament
                // mengganti isi grup dengan flyout ikon-grup saat sidebar
                // diminimize, bukan menampilkan ikon masing-masing menu.
                NavigationGroup::make('Manajemen Pengguna'),
                NavigationGroup::make('Manajemen Ujian')->collapsed(),
            ])
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
