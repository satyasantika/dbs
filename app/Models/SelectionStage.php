<?php

namespace App\Models;

use App\Models\User;
use App\Models\SelectionStep;
use App\Models\SelectionGuide;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SelectionStage extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'final' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function guide1()
    {
        return $this->belongsTo(User::class,'guide1_id');
    }

    public function guide2()
    {
        return $this->belongsTo(User::class,'guide2_id');
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

    public function selection_elements()
    {
        return $this->hasMany(SelectionElement::class);
    }

    public function selection_guides()
    {
        return $this->hasMany(SelectionGuide::class);
    }
}
