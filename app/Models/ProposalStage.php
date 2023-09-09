<?php

namespace App\Models;

use App\Models\User;
use App\Models\ProposalStep;
use App\Models\ProposalGuide;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProposalStage extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'is_final' => 'boolean',
    ];

    public function students(): belongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function proposalsteps(): hasMany
    {
        return $this->hasMany(ProposalStep::class);
    }

    public function proposalguides(): hasMany
    {
        return $this->hasMany(ProposalGuide::class);
    }
}
