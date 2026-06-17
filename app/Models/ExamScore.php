<?php

namespace App\Models;

use App\Models\User;
use App\Models\ExamRegistration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExamScore extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'revision' => 'integer',
        'pass_approved' => 'boolean',
        'scoring_edit_unlocked_at' => 'datetime',
    ];

    public function registration()
    {
        return $this->belongsTo(ExamRegistration::class,'exam_registration_id');
    }

    public function lecture()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}

