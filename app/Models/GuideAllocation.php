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
        'is_final' => 'boolean',
    ];

    public function guides(): belongsTo
    {
        return $this->belongsTo(User::class);
    }
}
