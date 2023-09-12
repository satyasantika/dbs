<?php

namespace App\Models;

use App\Models\User;
use App\Models\ProposalStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProposalGuide extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'is_accepted' => 'boolean',
    ];

    public function guides(): belongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function proposalstages(): belongsTo
    {
        return $this->belongsTo(ProposalStage::class);
    }
}
