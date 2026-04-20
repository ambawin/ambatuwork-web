<?php

namespace App\Policies;

use App\Enums\TeamMemberStatus;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Team $team): bool
    {
        return $this->hasActiveMembership($user, $team);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Team $team): bool
    {
        return $this->hasRole($user, $team, [TeamRole::Admin]);
    }

    public function manageMembers(User $user, Team $team): bool
    {
        return $this->hasRole($user, $team, [TeamRole::Admin]);
    }

    public function reviewTasks(User $user, Team $team): bool
    {
        return $this->hasRole($user, $team, [TeamRole::Admin, TeamRole::Supervisor]);
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