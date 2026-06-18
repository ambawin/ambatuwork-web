<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProjectInvitationRequest;
use App\Http\Resources\ProjectInvitationResource;
use App\Http\Resources\ProjectResource;
use App\Jobs\SendFcmNotificationJob;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

use OpenApi\Attributes as OA;

class ProjectInvitationController extends Controller
{
    #[OA\Get(
        path: '/invitations',
        summary: 'List Pending User Invitations',
        description: 'Returns pending invitations addressed to the authenticated user\'s email.',
        tags: ['Invitations'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful request',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/ProjectInvitation')
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
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

    #[OA\Get(
        path: '/projects/{project}/invitations',
        summary: 'List Project Invitations',
        description: 'Returns all pending invitations for the specified project.',
        tags: ['Invitations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'project',
                in: 'path',
                required: true,
                description: 'Project ID',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful request',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/ProjectInvitation')
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project not found')
        ]
    )]
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

    #[OA\Post(
        path: '/projects/{project}/invitations',
        summary: 'Create Project Invitation',
        description: 'Creates a pending invitation to join the project. The invitee is specified by email.',
        tags: ['Invitations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'project',
                in: 'path',
                required: true,
                description: 'Project ID',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'role'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'sam@example.com'),
                    new OA\Property(property: 'role', type: 'string', enum: ['member', 'supervisor'], example: 'member')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Invitation created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/ProjectInvitation')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
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

        // Dispatch push notification to the invited user (if they exist and have a device registered)
        $invitedUser = User::query()->whereRaw('lower(email) = ?', [$email])->first();

        if ($invitedUser !== null) {
            SendFcmNotificationJob::dispatch(
                userId: $invitedUser->id,
                title: "You've been invited to {$project->name}",
                body: "{$request->user()->name} invited you to join {$project->name} as {$request->string('role')}",
                data: [
                    'type' => 'project_invitation',
                    'invitation_token' => $plainToken,
                    'project_id' => (string) $project->id,
                ],
            );
        }

        return (new ProjectInvitationResource($invitation->load('project.owner')))->response()->setStatusCode(201);
    }

    #[OA\Delete(
        path: '/projects/{project}/invitations/{invitation}',
        summary: 'Revoke Project Invitation',
        description: 'Revokes a pending invitation.',
        tags: ['Invitations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'project',
                in: 'path',
                required: true,
                description: 'Project ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'invitation',
                in: 'path',
                required: true,
                description: 'Invitation ID',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Invitation revoked successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Invitation revoked.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ProjectInvitation')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Invitation not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
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

    #[OA\Post(
        path: '/invitations/{token}/accept',
        summary: 'Accept Invitation',
        description: 'Accepts a project invitation using the plain token string.',
        tags: ['Invitations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'token',
                in: 'path',
                required: true,
                description: 'Invitation Token',
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Invitation accepted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Invitation accepted.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Project')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Invitation not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
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

        SendFcmNotificationJob::dispatch(
            userId: $invitation->invited_by_user_id,
            title: "{$request->user()->name} joined {$invitation->project->name}",
            body: "{$request->user()->name} has accepted your invitation and joined as {$invitation->role}.",
            data: [
                'type' => 'project_invitation_accepted',
                'project_id' => (string) $invitation->project_id,
                'user_id' => (string) $request->user()->id,
            ],
        );

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