<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProjectInvitationRequest;
use App\Http\Resources\ProjectInvitationResource;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProjectInvitationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $invitations = ProjectInvitation::query()
            ->whereRaw('lower(email) = ?', [Str::lower($request->user()->email)])
            ->where('status', 'pending')
            ->with(['project.owner'])
            ->latest('id')
            ->get();

        return ProjectInvitationResource::collection($invitations);
    }

    public function projectIndex(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('invite', $project);

        $invitations = $project->invitations()
            ->where('status', 'pending')
            ->with(['project.owner'])
            ->latest('id')
            ->get();

        return ProjectInvitationResource::collection($invitations);
    }

    public function store(ProjectInvitationRequest $request, Project $project): JsonResponse
    {
        $this->authorize('invite', $project);

        $email = Str::lower($request->string('email')->toString());

        if (Str::lower($request->user()->email) === $email) {
            throw ValidationException::withMessages([
                'email' => ['You cannot invite yourself.'],
            ]);
        }

        $existingUserId = User::query()
            ->whereRaw('lower(email) = ?', [$email])
            ->value('id');

        if ($existingUserId && $project->memberships()->where('user_id', $existingUserId)->where('status', 'active')->exists()) {
            throw ValidationException::withMessages([
                'email' => ['This user is already a member.'],
            ]);
        }

        $plainToken = Str::random(64);

        $invitation = DB::transaction(function () use ($project, $request, $email, $plainToken): ProjectInvitation {
            return $project->invitations()->create([
                'email' => $email,
                'role' => $request->string('role')->toString(),
                'token' => $plainToken,
                'token_hash' => hash('sha256', $plainToken),
                'status' => 'pending',
                'invited_by_user_id' => $request->user()->id,
                'expires_at' => now()->addDays(7),
            ]);
        });

        return (new ProjectInvitationResource($invitation->load('project.owner')))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, Project $project, ProjectInvitation $invitation): JsonResponse
    {
        $this->authorize('invite', $project);

        abort_unless($invitation->project_id === $project->getKey(), 404);

        if ($invitation->status !== 'pending') {
            throw ValidationException::withMessages([
                'invitation' => ['Only pending invitations can be deleted.'],
            ]);
        }

        $invitation->forceFill([
            'status' => 'revoked',
        ])->save();

        return response()->json([
            'message' => 'Invitation revoked.',
            'data' => (new ProjectInvitationResource($invitation->refresh()->load('project.owner')))->resolve($request),
        ]);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = ProjectInvitation::query()
            ->where('token_hash', hash('sha256', $token))
            ->where('status', 'pending')
            ->with('project.memberships')
            ->firstOrFail();

        if ($invitation->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'token' => ['This invitation has expired.'],
            ]);
        }

        if (Str::lower($request->user()->email) !== Str::lower($invitation->email)) {
            throw ValidationException::withMessages([
                'token' => ['This invitation is not for your account.'],
            ]);
        }

        DB::transaction(function () use ($request, $invitation): void {
            $invitation->project->memberships()->updateOrCreate(
                [
                    'project_id' => $invitation->project_id,
                    'user_id' => $request->user()->id,
                ],
                [
                    'role' => $invitation->role,
                    'status' => 'active',
                    'invited_by_user_id' => $invitation->invited_by_user_id,
                    'joined_at' => now(),
                ]
            );

            $invitation->forceFill([
                'status' => 'accepted',
                'accepted_by_user_id' => $request->user()->id,
                'accepted_at' => now(),
            ])->save();
        });

        $project = $invitation->project->fresh()
            ->load(['owner', 'activeDefinitionOfDone'])
            ->loadCount([
                'memberships as active_memberships_count' => function ($query): void {
                    $query->where('status', 'active');
                },
                'backlogItems as backlog_items_count' => function ($query): void {
                    $query->where('status', '!=', 'archived');
                },
            ]);

        return response()->json([
            'message' => 'Invitation accepted.',
            'data' => (new ProjectResource($project))->resolve($request),
        ]);
    }
}