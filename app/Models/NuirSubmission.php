<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NuirSubmission extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'dbs_reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function dbsReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dbs_reviewer_id');
    }

    public function parentSubmission(): BelongsTo
    {
        return $this->belongsTo(NuirSubmission::class, 'parent_submission_id');
    }

    public function references(): HasMany
    {
        return $this->hasMany(NuirReference::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(NuirProposal::class);
    }

    public function assignment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(NuirAssignment::class);
    }

    public function isValidatorReviewable(): bool
    {
        return ! in_array($this->status, ['draft'], true);
    }

    public function isContentFinalForPembimbing(): bool
    {
        return $this->status === 'content_ok';
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'revision'], true);
    }

    public function hasActiveFinalProposal(): bool
    {
        return $this->proposals()->where('final', true)->exists();
    }
}
