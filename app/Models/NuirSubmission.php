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
        'title_saved_at' => 'datetime',
        'novelty_saved_at' => 'datetime',
        'urgency_saved_at' => 'datetime',
        'impact_saved_at' => 'datetime',
    ];

    public const REF_VALIDATION_NOT_STARTED = 'not_started';

    public const REF_VALIDATION_IN_PROGRESS = 'in_progress';

    public const REF_VALIDATION_COMPLETE = 'complete';

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

    public function childSubmissions(): HasMany
    {
        return $this->hasMany(NuirSubmission::class, 'parent_submission_id');
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
        if ($field === 'title') {
            return $this->isTitleSlot() || $this->isEditable() || $this->isPartialNuiEditable();
        }

        if ($this->isEditable()) {
            return true;
        }

        return $this->isPartialNuiEditable() && in_array($field, $this->rejectedNuiFields(), true);
    }

    public function validatedReferenceCount(): int
    {
        if ($this->relationLoaded('references')) {
            return $this->references->whereNotNull('ref_approved')->count();
        }

        return $this->references()->whereNotNull('ref_approved')->count();
    }

    public function totalReferenceCount(): int
    {
        if ($this->relationLoaded('references')) {
            return $this->references->count();
        }

        return $this->references()->count();
    }

    public function referenceValidationStatus(): string
    {
        $total = $this->totalReferenceCount();

        if ($total === 0) {
            return self::REF_VALIDATION_NOT_STARTED;
        }

        $validated = $this->validatedReferenceCount();

        if ($validated === 0) {
            return self::REF_VALIDATION_NOT_STARTED;
        }

        if ($validated >= $total) {
            return self::REF_VALIDATION_COMPLETE;
        }

        return self::REF_VALIDATION_IN_PROGRESS;
    }

    public function referenceValidationProgressLabel(): string
    {
        return $this->validatedReferenceCount().'/'.$this->totalReferenceCount();
    }

    public static function referenceValidationStatusLabel(string $status): string
    {
        return match ($status) {
            self::REF_VALIDATION_COMPLETE => 'Selesai',
            self::REF_VALIDATION_IN_PROGRESS => 'Berprogress',
            default => 'Belum berprogress',
        };
    }

    public static function referenceValidationStatusColor(string $status): string
    {
        return match ($status) {
            self::REF_VALIDATION_COMPLETE => 'success',
            self::REF_VALIDATION_IN_PROGRESS => 'warning',
            default => 'gray',
        };
    }
}
