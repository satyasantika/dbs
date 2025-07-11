<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViewExamScore extends Model
{
    use HasFactory;
    protected $table = 'view_exam_scores';

    protected $casts = [
        'revision' => 'boolean',
        'pass_approved' => 'boolean',
    ];

    public function registration()
    {
        return $this->belongsTo(ExamRegistration::class,'exam_registration_id');
    }

    public function viewregistration()
    {
        return $this->belongsTo(ViewExamRegistration::class,'exam_registration_id');
    }

    public function lecture()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
