<?php

namespace App\Models;

use App\Models\SelectionStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SelectionElement extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'approved' => 'boolean',
    ];

    public function stage()
    {
        return $this->belongsTo(SelectionStage::class,'selection_stage_id');
    }

    public function children()
    {
        return $this->hasMany(SelectionElement::class, 'parent_id');
    }
}
