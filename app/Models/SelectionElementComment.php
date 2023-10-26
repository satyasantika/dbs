<?php

namespace App\Models;

use App\Models\User;
use App\Models\SelectionElement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SelectionElementComment extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'revised' => 'boolean',
    ];

    public function verifiedBy()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function element()
    {
        return $this->belongsTo(SelectionElement::class,'selection_element_id');
    }
}
