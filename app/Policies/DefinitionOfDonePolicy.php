<?php

namespace App\Policies;

use App\Models\DefinitionOfDone;
use App\Models\Project;
use App\Models\User;

class DefinitionOfDonePolicy
{
    public function view(User $user, DefinitionOfDone $definitionOfDone): bool
    {
        return $definitionOfDone->project->isAccessibleTo($user);
    }

    public function create(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user);
    }

    public function update(User $user, DefinitionOfDone $definitionOfDone): bool
    {
        return $definitionOfDone->project->isOwnedBy($user);
    }

    public function delete(User $user, DefinitionOfDone $definitionOfDone): bool
    {
        return $definitionOfDone->project->isOwnedBy($user);
    }
}