<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

trait AuthorizesAdminPanelAccess
{
    public static function canViewAny(): bool
    {
        return filament()->getCurrentPanel()?->getId() === 'admin'
            && auth()->user()?->hasRole('admin');
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
