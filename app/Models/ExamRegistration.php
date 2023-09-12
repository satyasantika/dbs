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
    ];

    public function students(): belongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function examtypes(): belongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    public function examexaminer(): hasMany
    {
        return $this->hasMany(ExamExaminer::class);
    }
}

