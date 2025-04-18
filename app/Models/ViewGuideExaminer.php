<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViewGuideExaminer extends Model
{
    use HasFactory;
    protected $table = 'view_guide_examiners';

    public function student()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
