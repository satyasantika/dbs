<?php

namespace App\Models;

use App\Models\User;
use App\Models\SelectionStage;
use App\Models\GuideGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SelectionGuide extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'approved' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(GuideGroup::class,'guide_group_id');
    }

    public function stage()
    {
        return $this->belongsTo(SelectionStage::class,'selection_stage_id');
    }

    public function guide()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
