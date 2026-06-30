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

    public function contentReviews(): HasMany
    {
        return $this->hasMany(NuirContentReview::class);
    }

    public function revisionEvents(): HasMany
    {
        return $this->hasMany(NuirRevisionEvent::class);
    }

    public function isTitleSlot(): bool
    {
        return $this->status === 'title_slot';
    }

    /**
     * @return array{guide1: array{id: int, status: string}|null, guide2: array{id: int, status: string}|null}
     */
    public function lockedSeats(): array
    {
        $locked = ['guide1' => null, 'guide2' => null];

        foreach ($this->proposals()->orderByDesc('id')->get() as $proposal) {
            if ($locked['guide1'] === null && $proposal->guide1_status === 'accepted') {
                $locked['guide1'] = ['id' => $proposal->guide1_id, 'status' => 'accepted'];
            }

            if ($locked['guide2'] === null && $proposal->guide2_status === 'accepted') {
                $locked['guide2'] = ['id' => $proposal->guide2_id, 'status' => 'accepted'];
            }
        }

        return $locked;
    }

    public function contentReviewFor(User $user, NuirProposal $proposal, string $field): ?NuirContentReview
    {
        $role = match ($user->id) {
            $proposal->guide1_id => NuirContentReview::ROLE_GUIDE1,
            $proposal->guide2_id => NuirContentReview::ROLE_GUIDE2,
            default => null,
        };

        if (! $role) {
            return null;
        }

        return $this->contentReviews()
            ->where('user_id', $user->id)
            ->where('role', $role)
            ->where('field', $field)
            ->first();
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
        return in_array($this->status, ['title_slot', 'draft', 'revision'], true);
    }

    public function isReferencesEditable(): bool
    {
        return in_array($this->status, ['title_slot', 'draft', 'submitted', 'revision'], true);
    }

    public function hasActiveFinalProposal(): bool
    {
        return $this->proposals()->where('final', true)->exists();
    }

    /** @return list<string> */
    public function rejectedNuiFields(): array
    {
        return $this->contentReviews()
            ->where('approved', false)
            ->distinct()
            ->pluck('field')
            ->values()
            ->all();
    }

    public function hasRejectedReferences(): bool
    {
        return $this->references()->where('ref_approved', false)->exists();
    }

    public function hasPendingRevisions(): bool
    {
        return $this->hasRejectedReferences() || $this->rejectedNuiFields() !== [];
    }

    public function isPartialNuiEditable(): bool
    {
        return $this->rejectedNuiFields() !== []
            && in_array($this->status, ['submitted', 'content_ok'], true);
    }

    public function isNuiFieldEditable(string $field): bool
    {
        if ($this->isEditable()) {
            return true;
        }

        return $this->isPartialNuiEditable() && in_array($field, $this->rejectedNuiFields(), true);
    }
}
