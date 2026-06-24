<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

trait AuthorizesNuirRolePanelAccess
{
    protected static function currentPanelId(): ?string
    {
        return filament()->getCurrentPanel()?->getId();
    }

    protected static function nuirRoleAccessPermission(): string
    {
        return '';
    }

    protected static function userCanAccessCurrentPanel(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        $permission = static::nuirRoleAccessPermission();

        return $permission !== '' && $user->can($permission);
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
        return false;
    }
}
