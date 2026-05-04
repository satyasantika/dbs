<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as PermissionSpatie;
use Spatie\Permission\PermissionRegistrar;

class Permission extends PermissionSpatie
{
    use HasFactory;

    /**
     * True when permission is linked via role_has_permissions or model_has_permissions (users).
     */
    public function isAssignedToUsersOrRoles(): bool
    {
        return $this->roles()->exists() || $this->users()->exists();
    }

    protected static function booted(): void
    {
        static::saved(fn () => app(PermissionRegistrar::class)->forgetCachedPermissions());

        static::deleted(fn () => app(PermissionRegistrar::class)->forgetCachedPermissions());
    }
}
