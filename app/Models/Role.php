<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as RoleSpatie;
use Spatie\Permission\PermissionRegistrar;

class Role extends RoleSpatie
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(fn () => app(PermissionRegistrar::class)->forgetCachedPermissions());

        static::deleted(fn () => app(PermissionRegistrar::class)->forgetCachedPermissions());
    }
}
