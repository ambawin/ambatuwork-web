<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProjectMemberUpdateRequest;
use App\Http\Resources\ProjectMemberResource;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ProjectMemberController extends Controller
{
    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        $members = $project->memberships()
            ->with('user')
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        return ProjectMemberResource::collection($members);
    }

    public function update(ProjectMemberUpdateRequest $request, Project $project, User $user): ProjectMemberResource
    {
        $this->authorize('manageMembers', $project);

        if ($project->owner_user_id === $user->id) {
            throw ValidationException::withMessages([
                'user' => ['The project owner role cannot be changed.'],
            ]);
        }

        $membership = ProjectMembership::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        $membership->update([
            'role' => $request->string('role')->toString(),
        ]);

        return new ProjectMemberResource($membership->load('user'));
    }

    public function destroy(Request $request, Project $project, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        if ($project->owner_user_id === $user->id) {
            throw ValidationException::withMessages([
                'user' => ['The project owner cannot be removed.'],
            ]);
        }

        $membership = ProjectMembership::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        $membership->update([
            'status' => 'removed',
        ]);

        return response()->json([
            'message' => 'Member removed from project.',
        ]);
    }
}