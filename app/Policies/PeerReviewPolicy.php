<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class PeerReviewPolicy
{
    public function manageCycle(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user);
    }

    public function viewCycle(User $user, Project $project): bool
    {
        return $project->isAccessibleTo($user);
    }

    public function submitReview(User $user, Project $project): bool
    {
        if ($project->isOwnedBy($user)) {
            return true;
        }

        $role = $project->roleFor($user);
        return $role === 'member';
    }

    public function viewSummary(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user);
    }

    public function viewMySummary(User $user, Project $project): bool
    {
        if ($project->isOwnedBy($user)) {
            return true;
        }

        $role = $project->roleFor($user);
        return $role === 'member';
    }
}
