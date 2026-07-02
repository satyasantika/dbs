<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NuirRevisionEvent extends Model
{
    public const TYPE_NUI_REVISION = 'nui_revision';

    public const TYPE_REFERENCE_REVISION = 'reference_revision';

    public const TYPE_PROPOSAL_SELECTION = 'proposal_selection';

    public const TYPE_PROPOSAL_REJECTION = 'proposal_rejection';

    public const TYPE_PROPOSAL_CANCELLATION = 'proposal_cancellation';

    public const TYPE_DBS_REVISION = 'dbs_revision';

    public const ROLE_VALIDATOR = 'validator';

    public const ROLE_GUIDE1 = 'guide1';

    public const ROLE_GUIDE2 = 'guide2';

    public const ROLE_DBS = 'dbs';

    public const ROLE_MANAJER = 'manajer';

    public const ROLE_MAHASISWA = 'mahasiswa';

    protected $fillable = [
        'nuir_submission_id',
        'submission_version',
        'actor_id',
        'target_user_id',
        'actor_role',
        'event_type',
        'subject',
        'ref_order',
        'nuir_proposal_id',
        'note',
        'revision_fields',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'revision_fields' => 'array',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(NuirSubmission::class, 'nuir_submission_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(NuirProposal::class, 'nuir_proposal_id');
    }

    public function subjectLabel(): string
    {
        return match ($this->event_type) {
            self::TYPE_NUI_REVISION => ucfirst($this->subject),
            self::TYPE_REFERENCE_REVISION => 'Referensi #'.($this->ref_order ?? $this->subject),
            self::TYPE_PROPOSAL_SELECTION => 'Usulan '.($this->subject === 'guide1' ? 'P1' : 'P2'),
            self::TYPE_PROPOSAL_REJECTION => 'Penolakan '.strtoupper($this->subject),
            self::TYPE_PROPOSAL_CANCELLATION => 'Pembatalan '.($this->subject === 'guide1' ? 'P1' : 'P2'),
            self::TYPE_DBS_REVISION => 'NUIR (DBS)',
            default => $this->subject,
        };
    }

    public function actorRoleLabel(): string
    {
        return match ($this->actor_role) {
            self::ROLE_MAHASISWA => 'Mahasiswa',
            self::ROLE_VALIDATOR => 'Validator',
            self::ROLE_GUIDE1 => 'Pembimbing 1',
            self::ROLE_GUIDE2 => 'Pembimbing 2',
            self::ROLE_DBS => 'DBS',
            self::ROLE_MANAJER => 'Manajer',
            default => ucfirst((string) $this->actor_role),
        };
    }
}
