<?php

namespace App\Policies;

use App\Enums\TeamMemberStatus;
use App\Enums\TeamRole;
use App\Models\Sprint;
use App\Models\Team;
use App\Models\User;

class SprintPolicy
{
    public function viewAny(User $user, Team $team): bool
    {
        return $this->hasActiveMembership($user, $team);
    }

    public function view(User $user, Sprint $sprint): bool
    {
        return $this->hasActiveMembership($user, $sprint->team);
    }

    public function create(User $user, Team $team): bool
    {
        return $this->hasRole($user, $team, [TeamRole::Admin]);
    }

    public function update(User $user, Sprint $sprint): bool
    {
        return $this->hasRole($user, $sprint->team, [TeamRole::Admin]);
    }

    public function activate(User $user, Sprint $sprint): bool
    {
        return $this->hasRole($user, $sprint->team, [TeamRole::Admin]);
    }

    public function close(User $user, Sprint $sprint): bool
    {
        return $this->hasRole($user, $sprint->team, [TeamRole::Admin]);
    }

    private function hasActiveMembership(User $user, Team $team): bool
    {
        return $team->members()
            ->where('user_id', $user->id)
            ->where('status', TeamMemberStatus::Active)
            ->exists();
    }

    private function hasRole(User $user, Team $team, array $roles): bool
    {
        return $team->members()
            ->where('user_id', $user->id)
            ->where('status', TeamMemberStatus::Active)
            ->whereIn('role', array_map(fn ($role) => $role->value, $roles))
            ->exists();
    }
}