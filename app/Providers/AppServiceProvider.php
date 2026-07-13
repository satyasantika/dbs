<?php

namespace App\Providers;

use Livewire\Livewire;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            LogoutResponse::class,
            \App\Http\Responses\Auth\LogoutResponse::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Redirect Filament's login page to the app's own login page.
        // Registered here (before AdminPanelProvider) so this route takes precedence.
        Route::middleware('web')
            ->get('admin/login', fn () => redirect()->route('login'))
            ->name('filament.admin.auth.login');

        // Force all generated URLs (route(), redirect('/...'), url()) to use APP_URL's
        // scheme and path prefix. Behind the Apache reverse proxy, Laravel's own root()
        // detection only sees host:port, not the /dbsmatematika prefix or that the
        // original request was https — so login redirects etc. would otherwise drop both.
        $subPath = rtrim((string) parse_url(config('app.url'), PHP_URL_PATH), '/');
        if (parse_url(config('app.url'), PHP_URL_SCHEME) === 'https') {
            URL::forceScheme('https');
        }
        URL::forceRootUrl(config('app.url'));

        // Fix Livewire asset/update routes when app is deployed in a subdirectory.
        // APP_URL may include a path prefix (e.g. http://example.com/dbsmatematika).
        // Without this, Livewire generates /livewire/update instead of /dbsmatematika/livewire/update,
        // breaking AJAX updates (widgets never load, components can't re-render).
        if ($subPath !== '') {
            Livewire::setUpdateRoute(function ($handle) use ($subPath) {
                return Route::post($subPath . '/livewire/update', $handle)
                    ->middleware('web');
            });

            Livewire::setScriptRoute(function ($handle) use ($subPath) {
                return Route::get($subPath . '/livewire/livewire.min.js', $handle);
            });
        }

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            fn (): \Illuminate\Contracts\View\View => view('filament.impersonation-banner'),
        );
    }
}
