<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViewExamRegistration extends Model
{
    use HasFactory;
    protected $table = 'view_exam_registrations';
    protected $casts = [
        'pass_exam' => 'boolean',
    ];
    
    public function guide1()
    {
        return $this->belongsTo(User::class,'guide1_id');
    }

    public function guide2()
    {
        return $this->belongsTo(User::class,'guide2_id');
    }
}
