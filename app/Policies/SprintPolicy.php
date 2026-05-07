<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;

class SprintPolicy
{
    public function view(User $user, Sprint|Project $subject): bool
    {
        if ($subject instanceof Sprint) {
            return $subject->project->isAccessibleTo($user);
        }

        return $subject->isAccessibleTo($user);
    }

    public function create(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user);
    }

    public function start(User $user, Sprint $sprint): bool
    {
        return $sprint->project->isOwnedBy($user);
    }

    public function close(User $user, Sprint $sprint): bool
    {
        return $sprint->project->isOwnedBy($user);
    }
}