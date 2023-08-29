<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as PermissionSpatie;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends PermissionSpatie
{
    use HasFactory;
}
