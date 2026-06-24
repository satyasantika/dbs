<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NuirProposal extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'final' => 'boolean',
        'guide1_responded_at' => 'datetime',
        'guide2_responded_at' => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(NuirSubmission::class, 'nuir_submission_id');
    }

    public function guide1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guide1_id');
    }

    public function guide2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guide2_id');
    }

    public function isBothAccepted(): bool
    {
        return $this->guide1_status === 'accepted' && $this->guide2_status === 'accepted';
    }
}
