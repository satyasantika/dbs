<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NuirReferenceReview extends Model
{
    public const ROLE_GUIDE1 = 'guide1';

    public const ROLE_GUIDE2 = 'guide2';

    protected $fillable = [
        'nuir_reference_id',
        'user_id',
        'role',
        'approved',
        'note',
        'reviewed_at',
    ];

    protected $casts = [
        'approved' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function reference(): BelongsTo
    {
        return $this->belongsTo(NuirReference::class, 'nuir_reference_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
