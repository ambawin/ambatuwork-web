<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\RetroItem;
use App\Models\User;

class RetrospectivePolicy
{
    public function view(User $user, Project $project): bool
    {
        return $project->isAccessibleTo($user);
    }

    public function create(User $user, Project $project): bool
    {
        if ($project->isOwnedBy($user)) {
            return true;
        }

        $role = $project->roleFor($user);
        return $role === 'member';
    }

    public function createItem(User $user, Project $project): bool
    {
        if ($project->isOwnedBy($user)) {
            return true;
        }

        $role = $project->roleFor($user);
        return $role === 'member';
    }

    public function updateItem(User $user, Project $project, RetroItem $retroItem): bool
    {
        if ($project->isOwnedBy($user)) {
            return true;
        }

        $role = $project->roleFor($user);
        return $role === 'member' && $retroItem->author_user_id === $user->id;
    }

    public function deleteItem(User $user, Project $project, RetroItem $retroItem): bool
    {
        if ($project->isOwnedBy($user)) {
            return true;
        }

        $role = $project->roleFor($user);
        return $role === 'member' && $retroItem->author_user_id === $user->id;
    }
}
