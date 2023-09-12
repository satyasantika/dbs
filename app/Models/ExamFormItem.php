<?php

namespace App\Models;

use App\Models\ExamType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExamFormItem extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function examtypes(): belongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    public function examscores(): hasMany
    {
        return $this->hasMany(ExamScore::class);
    }
}

