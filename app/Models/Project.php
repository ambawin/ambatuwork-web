<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_user_id',
        'name',
        'description',
        'product_goal',
        'default_sprint_length_days',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'default_sprint_length_days' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ProjectMembership::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(ProjectInvitation::class);
    }

    public function definitionsOfDone(): HasMany
    {
        return $this->hasMany(DefinitionOfDone::class);
    }

    public function activeDefinitionOfDone(): HasOne
    {
        return $this->hasOne(DefinitionOfDone::class)->where('is_active', true);
    }

    public function backlogItems(): HasMany
    {
        return $this->hasMany(BacklogItem::class);
    }

    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class);
    }

    public function activeSprint(): HasOne
    {
        return $this->hasOne(Sprint::class)->where('status', 'active');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_memberships')
            ->withPivot(['role', 'status', 'invited_by_user_id', 'joined_at'])
            ->withTimestamps()
            ->wherePivot('status', 'active');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user): void {
            $query->where('owner_user_id', $user->getKey())
                ->orWhereHas('memberships', function (Builder $membershipQuery) use ($user): void {
                    $membershipQuery->where('user_id', $user->getKey())
                        ->where('status', 'active');
                });
        });
    }

    public function membershipFor(User $user): ?ProjectMembership
    {
        if ($this->relationLoaded('memberships')) {
            return $this->memberships->firstWhere('user_id', $user->getKey());
        }

        return $this->memberships()
            ->where('user_id', $user->getKey())
            ->first();
    }

    public function isAccessibleTo(User $user): bool
    {
        if ($this->owner_user_id === $user->getKey()) {
            return true;
        }

        return $this->memberships()
            ->where('user_id', $user->getKey())
            ->where('status', 'active')
            ->exists();
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->owner_user_id === $user->getKey();
    }

    public function roleFor(User $user): ?string
    {
        return $this->membershipFor($user)?->role;
    }

    public function dailyCheckins(): HasMany
    {
        return $this->hasMany(DailyCheckin::class);
    }

    public function impediments(): HasMany
    {
        return $this->hasMany(Impediment::class);
    }

    public function sprintReviews(): HasMany
    {
        return $this->hasMany(SprintReview::class);
    }

    public function retrospectives(): HasMany
    {
        return $this->hasMany(Retrospective::class);
    }

    public function peerReviewCycles(): HasMany
    {
        return $this->hasMany(PeerReviewCycle::class);
    }
}