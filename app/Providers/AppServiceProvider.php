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

        // Fix Livewire's script tag URL when app is deployed in a subdirectory.
        // APP_URL may include a path prefix (e.g. http://example.com/dbsmatematika).
        // FrontendAssets::js() reads the route's raw URI directly (bypassing
        // URL::forceRootUrl()), so the prefix must be baked into the route itself
        // here, otherwise the <script src> misses /dbsmatematika entirely.
        //
        // The update (AJAX) route is intentionally left at its default,
        // unprefixed URI: HandleRequests::getUpdateUri() resolves it through
        // UrlGenerator::toRoute(), which already honors forceRootUrl() above —
        // prefixing it here too would duplicate the subpath (e.g.
        // /dbsmatematika/dbsmatematika/livewire/update).
        if ($subPath !== '') {
            Livewire::setScriptRoute(function ($handle) use ($subPath) {
                return Route::get($subPath . '/livewire/livewire.min.js', $handle);
            });
        }

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            fn (): \Illuminate\Contracts\View\View => view('filament.impersonation-banner'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::STYLES_AFTER,
            fn (): \Illuminate\Contracts\View\View => view('filament.shared.custom-styles'),
        );
    }
}
