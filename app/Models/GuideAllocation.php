<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GuideAllocation extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function lecture()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
