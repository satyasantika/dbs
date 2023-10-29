<?php

namespace App\Models;

use App\Models\SelectionGuideGroup;
use App\Models\SelectionStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SelectionGuide extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'is_accepted' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(SelectionGuideGroup::class,'selection_guide_group_id');
    }

    public function stage()
    {
        return $this->belongsTo(SelectionStage::class,'selection_stage_id');
    }
}
