<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GuideExaminer extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'boolean',
        'proposal_date' => 'date',
        'seminar_date' => 'date',
        'thesis_date' => 'date',
    ];

    public $timestamps = false;

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
}
