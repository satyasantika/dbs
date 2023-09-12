<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalStep extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    public function proposalstages(): belongsTo
    {
        return $this->belongsTo(ProposalStage::class);
    }
}
