<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NuirReference extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'ref_approved' => 'boolean',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(NuirSubmission::class, 'nuir_submission_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(NuirReferenceReview::class);
    }

    public function guideReviewFor(User $user, NuirProposal $proposal): ?NuirReferenceReview
    {
        $role = match ($user->id) {
            $proposal->guide1_id => NuirReferenceReview::ROLE_GUIDE1,
            $proposal->guide2_id => NuirReferenceReview::ROLE_GUIDE2,
            default => null,
        };

        if (! $role) {
            return null;
        }

        return $this->reviews()->where('user_id', $user->id)->where('role', $role)->first();
    }
}
