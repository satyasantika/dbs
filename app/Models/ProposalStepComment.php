<?php

namespace App\Models;

use App\Models\User;
use App\Models\ProposalStep;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProposalStepComment extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'need_revision' => 'boolean',
    ];

    public function guides(): belongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function proposalsteps(): belongsTo
    {
        return $this->belongsTo(ProposalStep::class);
    }
}
