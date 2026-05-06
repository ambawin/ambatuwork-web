<?php

namespace App\Policies;

use App\Models\BacklogItem;
use App\Models\Project;
use App\Models\User;

class BacklogItemPolicy
{
    public function view(User $user, BacklogItem $backlogItem): bool
    {
        return $backlogItem->project->isAccessibleTo($user);
    }

    public function create(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user) || in_array($project->roleFor($user), ['owner', 'member'], true);
    }

    public function update(User $user, BacklogItem $backlogItem): bool
    {
        return $backlogItem->project->isOwnedBy($user) || in_array($backlogItem->project->roleFor($user), ['owner', 'member'], true);
    }

    public function delete(User $user, BacklogItem $backlogItem): bool
    {
        return $backlogItem->project->isOwnedBy($user) || in_array($backlogItem->project->roleFor($user), ['owner', 'member'], true);
    }
}