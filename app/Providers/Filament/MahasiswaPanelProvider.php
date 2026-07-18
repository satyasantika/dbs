<?php

namespace App\Providers\Filament;

use App\Filament\Mahasiswa\Pages\CreateNuirProposal;
use App\Filament\Mahasiswa\Pages\CreateNuirSubmission;
use App\Filament\Mahasiswa\Pages\Dashboard;
use App\Filament\Mahasiswa\Pages\EditNuirSubmission;
use App\Filament\Mahasiswa\Pages\MahasiswaEditProfile;
use App\Filament\Mahasiswa\Pages\NuirProposalOverview;
use App\Filament\Mahasiswa\Pages\NuirSubmissionOverview;
use App\Filament\Mahasiswa\Pages\ReviseNuirSubmission;
use App\Http\Middleware\FilamentAuthenticate as Authenticate;
use App\Support\FilamentBrand;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
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

class MahasiswaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('mahasiswa')
            ->path('mahasiswa')
            ->login(false)
            ->brandName(fn () => FilamentBrand::withHomeIcon('Portal Mahasiswa'))
            ->homeUrl(fn () => route('home'))
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            ->collapsedSidebarWidth('3.5rem')
            ->profile(MahasiswaEditProfile::class, isSimple: false)
            ->maxContentWidth(MaxWidth::Full)
            ->discoverPages(in: app_path('Filament/Mahasiswa/Pages'), for: 'App\\Filament\\Mahasiswa\\Pages')
            ->discoverWidgets(in: app_path('Filament/Mahasiswa/Widgets'), for: 'App\\Filament\\Mahasiswa\\Widgets')
            ->pages([
                Dashboard::class,
                \App\Filament\Shared\Pages\EditProfile::class,
                NuirSubmissionOverview::class,
                CreateNuirSubmission::class,
                EditNuirSubmission::class,
                ReviseNuirSubmission::class,
                NuirProposalOverview::class,
                CreateNuirProposal::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('NUIR'),
            ])
            ->userMenuItems([
                // "Ganti Password" sudah muncul otomatis lewat ->profile()
                // di atas (MahasiswaEditProfile) — cukup tambahkan Edit Profil.
                MenuItem::make()
                    ->label('Edit Profil')
                    ->icon('heroicon-o-user-circle')
                    ->url(fn (): string => \App\Filament\Shared\Pages\EditProfile::getUrl()),
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
