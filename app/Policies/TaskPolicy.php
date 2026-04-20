<?php

namespace App\Policies;

use App\Enums\SprintStatus;
use App\Enums\SubmissionReviewStatus;
use App\Enums\TaskStatus;
use App\Enums\TeamMemberStatus;
use App\Enums\TeamRole;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return $this->hasActiveMembership($user, $task->sprint->team);
    }

    public function create(User $user, Team $team): bool
    {
        return $this->hasRole($user, $team, [TeamRole::Admin]);
    }

    public function update(User $user, Task $task): bool
    {
        return $this->hasRole($user, $task->sprint->team, [TeamRole::Admin]);
    }

    public function submit(User $user, Task $task): bool
    {
        return $task->assignee_user_id === $user->id
            && $this->hasActiveMembership($user, $task->sprint->team)
            && in_array($task->status, [TaskStatus::Todo, TaskStatus::InProgress, TaskStatus::Rejected], true)
            && in_array($task->sprint->status, [SprintStatus::Active, SprintStatus::Review], true);
    }

    public function review(User $user, Task $task): bool
    {
        if (! $this->hasRole($user, $task->sprint->team, [TeamRole::Admin, TeamRole::Supervisor])) {
            return false;
        }

        if ($task->assignee_user_id === $user->id) {
            return false;
        }

        $latestSubmission = $task->latestSubmission;

        return $latestSubmission !== null
            && $latestSubmission->review_status === SubmissionReviewStatus::Pending;
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