<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NuirContentReview extends Model
{
    public const ROLE_GUIDE1 = 'guide1';

    public const ROLE_GUIDE2 = 'guide2';

    public const FIELD_TITLE = 'title';

    public const FIELD_NOVELTY = 'novelty';

    public const FIELD_URGENCY = 'urgency';

    public const FIELD_IMPACT = 'impact';

    public const FIELDS = [
        self::FIELD_TITLE,
        self::FIELD_NOVELTY,
        self::FIELD_URGENCY,
        self::FIELD_IMPACT,
    ];

    protected $fillable = [
        'nuir_submission_id',
        'user_id',
        'role',
        'field',
        'approved',
        'note',
        'reviewed_at',
    ];

    protected $casts = [
        'approved' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(NuirSubmission::class, 'nuir_submission_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
