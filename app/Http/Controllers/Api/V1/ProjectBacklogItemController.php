<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProjectBacklogItemRequest;
use App\Http\Requests\Api\V1\ProjectBacklogItemUpdateRequest;
use App\Http\Resources\BacklogItemResource;
use App\Models\BacklogItem;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

use OpenApi\Attributes as OA;

class ProjectBacklogItemController extends Controller
{
    #[OA\Get(
        path: '/projects/{project}/backlog-items',
        summary: 'List Backlog Items',
        description: 'Returns all non-archived backlog items for the project, ordered by priority rank.',
        tags: ['Backlog Items'],
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
                            items: new OA\Items(ref: '#/components/schemas/BacklogItem')
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

        $backlogItems = $project->backlogItems()
            ->where('status', '!=', 'archived')
            ->with(['createdBy', 'assignedTo'])
            ->orderByRaw("CASE 
                WHEN priority = 'highest' THEN 1 
                WHEN priority = 'high' THEN 2 
                WHEN priority = 'medium' THEN 3 
                WHEN priority = 'low' THEN 4 
                WHEN priority = 'lowest' THEN 5 
                ELSE 6 
            END ASC, id DESC")
            ->get();

        return BacklogItemResource::collection($backlogItems);
    }

    #[OA\Post(
        path: '/projects/{project}/backlog-items',
        summary: 'Create Backlog Item',
        description: 'Creates a new backlog item in the project.',
        tags: ['Backlog Items'],
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
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Add dark mode'),
                    new OA\Property(property: 'description', type: 'string', maxLength: 5000, nullable: true, example: 'Support theme switching'),
                    new OA\Property(property: 'type', type: 'string', enum: ['story', 'task', 'bug', 'improvement'], default: 'story', example: 'story'),
                    new OA\Property(property: 'priority', type: 'string', enum: ['highest', 'high', 'medium', 'low', 'lowest'], default: 'medium', example: 'medium'),
                    new OA\Property(property: 'estimate_points', type: 'integer', minimum: 1, maximum: 100, nullable: true, example: 5),
                    new OA\Property(
                        property: 'acceptance_criteria',
                        type: 'array',
                        items: new OA\Items(type: 'string', maxLength: 1000),
                        nullable: true,
                        example: ['User can toggle dark mode', 'Preference persists after refresh']
                    ),
                    new OA\Property(property: 'assigned_to_user_id', type: 'integer', nullable: true, example: 2)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Backlog item created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/BacklogItem')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    public function store(ProjectBacklogItemRequest $request, Project $project): JsonResponse
    {
        $this->authorize('manageBacklog', $project);

        $validated = $request->validated();

        $backlogItem = $project->backlogItems()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? 'story',
            'status' => 'backlog',
            'priority' => $validated['priority'] ?? 'medium',
            'estimate_points' => $validated['estimate_points'] ?? null,
            'acceptance_criteria' => $validated['acceptance_criteria'] ?? null,
            'created_by_user_id' => $request->user()->id,
            'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
        ])->load(['createdBy', 'assignedTo']);

        return (new BacklogItemResource($backlogItem))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/projects/{project}/backlog-items/{backlogItem}',
        summary: 'Get Backlog Item',
        description: 'Returns details of a single backlog item.',
        tags: ['Backlog Items'],
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
                name: 'backlogItem',
                in: 'path',
                required: true,
                description: 'Backlog Item ID',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful request',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/BacklogItem')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Backlog Item not found')
        ]
    )]
    public function show(Request $request, Project $project, BacklogItem $backlogItem): BacklogItemResource
    {
        $this->authorize('view', $project);

        abort_unless($backlogItem->project_id === $project->getKey(), 404);

        return new BacklogItemResource($backlogItem->load(['createdBy', 'assignedTo']));
    }

    #[OA\Patch(
        path: '/projects/{project}/backlog-items/{backlogItem}',
        summary: 'Update Backlog Item',
        description: 'Updates backlog item details. All fields are optional.',
        tags: ['Backlog Items'],
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
                name: 'backlogItem',
                in: 'path',
                required: true,
                description: 'Backlog Item ID',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Add dark mode'),
                    new OA\Property(property: 'description', type: 'string', maxLength: 5000, nullable: true, example: 'New description'),
                    new OA\Property(property: 'type', type: 'string', enum: ['story', 'task', 'bug', 'improvement'], example: 'story'),
                    new OA\Property(property: 'status', type: 'string', enum: ['backlog', 'ready', 'selected', 'in_progress', 'in_review', 'done', 'archived'], example: 'in_progress'),
                    new OA\Property(property: 'priority', type: 'string', enum: ['highest', 'high', 'medium', 'low', 'lowest'], example: 'medium'),
                    new OA\Property(property: 'estimate_points', type: 'integer', minimum: 1, maximum: 100, nullable: true, example: 8),
                    new OA\Property(
                        property: 'acceptance_criteria',
                        type: 'array',
                        items: new OA\Items(type: 'string', maxLength: 1000),
                        nullable: true,
                        example: ['User can toggle dark mode']
                    ),
                    new OA\Property(property: 'assigned_to_user_id', type: 'integer', nullable: true, example: 2)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Backlog item updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/BacklogItem')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Backlog Item not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    public function update(ProjectBacklogItemUpdateRequest $request, Project $project, BacklogItem $backlogItem): JsonResponse
    {
        $this->authorize('manageBacklog', $project);

        abort_unless($backlogItem->project_id === $project->getKey(), 404);

        $validated = $request->validated();

        $backlogItem->fill($validated)->save();

        return (new BacklogItemResource($backlogItem->fresh()->load(['createdBy', 'assignedTo'])))->response();
    }

    #[OA\Delete(
        path: '/projects/{project}/backlog-items/{backlogItem}',
        summary: 'Archive Backlog Item',
        description: 'Archives a backlog item by setting its status to archived.',
        tags: ['Backlog Items'],
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
                name: 'backlogItem',
                in: 'path',
                required: true,
                description: 'Backlog Item ID',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Backlog item archived successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Backlog item archived.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/BacklogItem')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Backlog Item not found')
        ]
    )]
    public function destroy(Request $request, Project $project, BacklogItem $backlogItem): JsonResponse
    {
        $this->authorize('manageBacklog', $project);

        abort_unless($backlogItem->project_id === $project->getKey(), 404);

        $backlogItem->update([
            'status' => 'archived',
        ]);

        return response()->json([
            'message' => 'Backlog item archived.',
            'data' => (new BacklogItemResource($backlogItem->refresh()->load(['createdBy', 'assignedTo'])))->resolve($request),
        ]);
    }

}