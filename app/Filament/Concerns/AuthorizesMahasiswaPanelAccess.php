<?php

namespace App\Filament\Concerns;

trait AuthorizesMahasiswaPanelAccess
{
    protected static function currentPanelId(): ?string
    {
        return filament()->getCurrentPanel()?->getId();
    }

    protected static function mahasiswaAccessPermission(): string
    {
        return '';
    }

    protected static function userCanAccessCurrentPanel(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (! $user->hasRole('mahasiswa')) {
            return false;
        }

        $permission = static::mahasiswaAccessPermission();

        return $permission === '' || $user->can($permission);
    }

    public static function canAccess(): bool
    {
        return static::userCanAccessCurrentPanel();
    }
}
