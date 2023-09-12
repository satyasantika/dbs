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
        'is_chief' => 'boolean',
        'need_revision' => 'boolean',
        'is_approved' => 'boolean',
    ];

    public function registrations(): belongsTo
    {
        return $this->belongsTo(ExamRegistration::class);
    }

    public function examiners(): belongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function examscores(): hasMany
    {
        return $this->hasMany(ExamScore::class);
    }
}

