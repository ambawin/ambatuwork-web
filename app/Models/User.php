<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'google_id',
        'avatar_url',
        'password',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_user_id');
    }

    public function projectMemberships(): HasMany
    {
        return $this->hasMany(ProjectMembership::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_memberships')
            ->withPivot(['role', 'status', 'invited_by_user_id', 'joined_at'])
            ->wherePivot('status', 'active')
            ->withTimestamps();
    }

    public function sentProjectInvitations(): HasMany
    {
        return $this->hasMany(ProjectInvitation::class, 'invited_by_user_id');
    }

    public function acceptedProjectInvitations(): HasMany
    {
        return $this->hasMany(ProjectInvitation::class, 'accepted_by_user_id');
    }
}