<?php

namespace App\Models;

use App\Models\ExamExaminer;
use App\Models\ExamFormItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExamScore extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function examexaminers(): belongsTo
    {
        return $this->belongsTo(ExamExaminer::class);
    }

    public function examformitems(): belongsTo
    {
        return $this->belongsTo(ExamFormItem::class);
    }
}

