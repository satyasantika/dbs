<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\ExamExaminer;
use App\Models\ProposalGuide;
use App\Models\ProposalStage;
use App\Models\ExamRegistration;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'gender',
        'birth_place',
        'birth_date',
        'address',
        'phone',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birth_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function proposalstudents(): hasMany
    {
        return $this->hasMany(ProposalStage::class);
    }

    public function proposalguides(): hasMany
    {
        return $this->hasMany(ProposalGuide::class);
    }

    public function examregistrations(): hasMany
    {
        return $this->hasMany(ExamRegistration::class);
    }

    public function examexaminer(): hasMany
    {
        return $this->hasMany(ExamExaminer::class);
    }
}
