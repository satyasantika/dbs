<?php

namespace App\Providers;

use Livewire\Livewire;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse;

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

        // Fix Livewire asset/update routes when app is deployed in a subdirectory.
        // APP_URL may include a path prefix (e.g. http://example.com/dbsmatematika).
        // Without this, Livewire generates /livewire/update instead of /dbsmatematika/livewire/update,
        // breaking AJAX updates (widgets never load, components can't re-render).
        $subPath = rtrim((string) parse_url(config('app.url'), PHP_URL_PATH), '/');
        if ($subPath !== '') {
            Livewire::setUpdateRoute(function ($handle) use ($subPath) {
                return Route::post($subPath . '/livewire/update', $handle)
                    ->middleware('web');
            });

            Livewire::setScriptRoute(function ($handle) use ($subPath) {
                return Route::get($subPath . '/livewire/livewire.min.js', $handle);
            });
        }
    }
}
