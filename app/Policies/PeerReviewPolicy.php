<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Models\PeerReviewCycle;

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

    public function viewSummary(User $user, PeerReviewCycle $cycle, Project $project): bool
    {
        if ($project->isOwnedBy($user)) {
            return true;
        }

        return $cycle->status === 'closed' && $project->isAccessibleTo($user);
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
