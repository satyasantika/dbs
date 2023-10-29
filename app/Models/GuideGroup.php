<?php

namespace App\Models;

use App\Models\GuideAllocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GuideGroup extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function allocation()
    {
        return $this->belongsTo(GuideAllocation::class,'guide_allocation_id');
    }
}
