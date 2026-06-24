<?php

namespace App\Providers\Filament;

use App\Filament\Mahasiswa\Pages\CreateNuirProposal;
use App\Filament\Mahasiswa\Pages\CreateNuirSubmission;
use App\Filament\Mahasiswa\Pages\Dashboard;
use App\Filament\Mahasiswa\Pages\EditNuirSubmission;
use App\Filament\Mahasiswa\Pages\NuirProposalOverview;
use App\Filament\Mahasiswa\Pages\NuirSubmissionOverview;
use App\Filament\Mahasiswa\Pages\ReviseNuirSubmission;
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

class MahasiswaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('mahasiswa')
            ->path('mahasiswa')
            ->login(false)
            ->brandName('Portal Mahasiswa')
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth(MaxWidth::SevenExtraLarge)
            ->discoverPages(in: app_path('Filament/Mahasiswa/Pages'), for: 'App\\Filament\\Mahasiswa\\Pages')
            ->pages([
                Dashboard::class,
                NuirSubmissionOverview::class,
                CreateNuirSubmission::class,
                EditNuirSubmission::class,
                ReviseNuirSubmission::class,
                NuirProposalOverview::class,
                CreateNuirProposal::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('NUIR')->icon('heroicon-o-document-text'),
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
