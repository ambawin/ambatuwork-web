<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class SprintReviewPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $project->isAccessibleTo($user);
    }

    public function create(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user);
    }
}
