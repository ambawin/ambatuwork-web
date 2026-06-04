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

use OpenApi\Attributes as OA;

class ProjectMemberController extends Controller
{
    #[OA\Get(
        path: '/projects/{project}/members',
        summary: 'List Project Members',
        description: 'Returns a list of all active members and supervisors in the specified project.',
        tags: ['Project Members'],
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
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'project_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'user_id', type: 'integer', example: 2),
                                    new OA\Property(property: 'role', type: 'string', example: 'member'),
                                    new OA\Property(property: 'status', type: 'string', example: 'active'),
                                    new OA\Property(property: 'joined_at', type: 'string', format: 'date-time', example: '2026-06-01T12:00:00.000000Z'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-06-01T12:00:00.000000Z'),
                                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-06-01T12:00:00.000000Z'),
                                    new OA\Property(property: 'user', ref: '#/components/schemas/User')
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project not found')
        ]
    )]
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

    #[OA\Patch(
        path: '/projects/{project}/members/{user}',
        summary: 'Change Member Role',
        description: 'Updates a project member\'s role (e.g. from member to supervisor). The owner\'s role cannot be changed.',
        tags: ['Project Members'],
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
                name: 'user',
                in: 'path',
                required: true,
                description: 'User ID of the member',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['role'],
                properties: [
                    new OA\Property(property: 'role', type: 'string', enum: ['member', 'supervisor'], example: 'supervisor')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Member role updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'project_id', type: 'integer', example: 1),
                                new OA\Property(property: 'user_id', type: 'integer', example: 2),
                                new OA\Property(property: 'role', type: 'string', example: 'supervisor'),
                                new OA\Property(property: 'status', type: 'string', example: 'active'),
                                new OA\Property(property: 'joined_at', type: 'string', format: 'date-time', example: '2026-06-01T12:00:00.000000Z'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-06-01T12:00:00.000000Z'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-06-01T12:00:00.000000Z'),
                                new OA\Property(property: 'user', ref: '#/components/schemas/User')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Member not found'),
            new OA\Response(response: 422, description: 'Validation failed / Owner role cannot be changed')
        ]
    )]
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

    #[OA\Delete(
        path: '/projects/{project}/members/{user}',
        summary: 'Remove Member',
        description: 'Removes a member from the project. The owner cannot be removed.',
        tags: ['Project Members'],
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
                name: 'user',
                in: 'path',
                required: true,
                description: 'User ID of the member to remove',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Member removed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Member removed from project.')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Member not found'),
            new OA\Response(response: 422, description: 'Validation failed / Owner cannot be removed')
        ]
    )]
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