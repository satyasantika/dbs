<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

trait AuthorizesDbsPanelAccess
{
    protected static function currentPanelId(): ?string
    {
        return filament()->getCurrentPanel()?->getId();
    }

    protected static function userCanAccessCurrentPanel(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return match (static::currentPanelId()) {
            'admin' => $user->hasRole('admin'),
            'dbs' => filled(static::dbsAccessPermission()) && $user->can(static::dbsAccessPermission()),
            default => false,
        };
    }

    protected static function dbsAccessPermission(): string
    {
        return '';
    }

    public static function canViewAny(): bool
    {
        return static::userCanAccessCurrentPanel();
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canViewAny();
    }
}
