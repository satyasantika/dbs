<?php

namespace App\Providers;

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
    }
}
