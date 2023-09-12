<?php

namespace App\Models;

use App\Models\ExamFormItem;
use App\Models\ExamRegistration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamType extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function examformitems(): hasMany
    {
        return $this->hasMany(ExamFormItem::class);
    }

    public function examregistrations(): hasMany
    {
        return $this->hasMany(ExamRegistration::class);
    }
}
