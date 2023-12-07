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

    public function lecture()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
