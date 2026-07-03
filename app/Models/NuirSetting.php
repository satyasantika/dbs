<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NuirSetting extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'boolean',
        'deadline' => 'date',
        'stage_starts_at' => 'date',
    ];

    /**
     * "Aktif" requires both the manual toggle AND (when a stage window is
     * configured) being within [stage_starts_at, deadline]. Either date
     * left null means that boundary doesn't gate — preserves existing
     * settings that don't define a window.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true)
            ->where(fn (Builder $q) => $q->whereNull('stage_starts_at')->orWhere('stage_starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('deadline')->orWhere('deadline', '>=', now()));
    }
}
