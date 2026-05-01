<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\ExamExaminer;
use App\Models\SelectionGuide;
use App\Models\SelectionStage;
use App\Models\ExamRegistration;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, Impersonate;

    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'initial',
        'gender',
        'birth_place',
        'birth_date',
        'address',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birth_date' => 'date',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('admin');
    }

    public function canImpersonate(): bool
    {
        return Auth::user()->hasRole('admin');
    }

    public function canBeImpersonated(): bool
    {
        return ! $this->hasRole('admin');
    }

    public function selectionstudents(): HasMany
    {
        return $this->hasMany(SelectionStage::class);
    }

    public function selectionguides(): HasMany
    {
        return $this->hasMany(SelectionGuide::class);
    }

    public function examregistrations(): HasMany
    {
        return $this->hasMany(ExamRegistration::class);
    }

    public function examexaminer(): HasMany
    {
        return $this->hasMany(ExamExaminer::class);
    }
}
