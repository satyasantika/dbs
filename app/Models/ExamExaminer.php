<?php

namespace App\Models;

use App\Models\User;
use App\Models\ExamScore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamExaminer extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'need_revision' => 'boolean',
        'is_approved' => 'boolean',
        'is_chief' => 'boolean',
    ];

    public function examiners(): belongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function examscores(): hasMany
    {
        return $this->hasMany(ExamScore::class);
    }
}

