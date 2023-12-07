<?php

namespace App\Models;

use App\Models\User;
use App\Models\ExamType;
use App\Models\ExamExaminer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExamRegistration extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'exam_date' => 'date',
        'pass_exam' => 'boolean',
    ];

    public function examtype()
    {
        return $this->belongsTo(ExamType::class,'exam_type_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function examiner1()
    {
        return $this->belongsTo(User::class,'examiner1_id');
    }

    public function examiner2()
    {
        return $this->belongsTo(User::class,'examiner2_id');
    }

    public function examiner3()
    {
        return $this->belongsTo(User::class,'examiner3_id');
    }

    public function guide1()
    {
        return $this->belongsTo(User::class,'guide1_id');
    }

    public function guide2()
    {
        return $this->belongsTo(User::class,'guide2_id');
    }

    public function chief()
    {
        return $this->belongsTo(User::class,'chief_id');
    }
}

