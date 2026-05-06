<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $project->isAccessibleTo($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user);
    }

    public function invite(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user);
    }

    public function manageMembers(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user);
    }

    public function manageDefinitionOfDone(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user);
    }

    public function manageBacklog(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user) || in_array($project->roleFor($user), ['owner', 'member'], true);
    }
}