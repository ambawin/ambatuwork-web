<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class DailyCheckinPolicy
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
}
