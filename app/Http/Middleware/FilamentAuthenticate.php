<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as FilamentMiddleware;

class FilamentAuthenticate extends FilamentMiddleware
{
    protected function redirectTo($request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
}
