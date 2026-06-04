<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRetroItemRequest;
use App\Http\Requests\StoreRetrospectiveRequest;
use App\Http\Resources\RetroItemResource;
use App\Http\Resources\RetrospectiveResource;
use App\Models\Project;
use App\Models\RetroItem;
use App\Models\Retrospective;
use App\Models\Sprint;
use Illuminate\Http\JsonResponse;

use OpenApi\Attributes as OA;

class RetrospectiveController extends Controller
{
    #[OA\Get(
        path: '/projects/{project}/sprints/{sprint}/retrospective',
        summary: 'Get Retrospective details',
        description: 'Returns the retrospective details and feedback items for the specified sprint.',
        tags: ['Retrospective'],
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
                name: 'sprint',
                in: 'path',
                required: true,
                description: 'Sprint ID',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful request',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Retrospective')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project, Sprint or Retrospective not found')
        ]
    )]
    public function show(Project $project, Sprint $sprint): RetrospectiveResource|JsonResponse
    {
        $this->authorize('view', [Retrospective::class, $project]);

        abort_unless($sprint->project_id === $project->id, 404);

        $retro = $sprint->retrospective()
            ->with(['items.author', 'items.assignee'])
            ->first();

        if (!$retro) {
            return response()->json(['message' => 'Retrospective not found.'], 404);
        }

        return new RetrospectiveResource($retro);
    }

    #[OA\Post(
        path: '/projects/{project}/sprints/{sprint}/retrospective',
        summary: 'Submit Retrospective Score',
        description: 'Submits or updates the overall team happiness score for a sprint retrospective.',
        tags: ['Retrospective'],
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
                name: 'sprint',
                in: 'path',
                required: true,
                description: 'Sprint ID',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['team_happiness_score'],
                properties: [
                    new OA\Property(property: 'team_happiness_score', type: 'integer', minimum: 1, maximum: 5, example: 5)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Retrospective score saved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Retrospective')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Sprint not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    public function store(StoreRetrospectiveRequest $request, Project $project, Sprint $sprint): RetrospectiveResource
    {
        $this->authorize('create', [Retrospective::class, $project]);

        abort_unless($sprint->project_id === $project->id, 404);

        $retro = Retrospective::updateOrCreate(
            ['sprint_id' => $sprint->id],
            [
                'project_id' => $project->id,
                'team_happiness_score' => $request->input('team_happiness_score'),
            ]
        );

        return new RetrospectiveResource($retro->load(['items.author', 'items.assignee']));
    }

    #[OA\Post(
        path: '/projects/{project}/sprints/{sprint}/retrospective/items',
        summary: 'Create Retrospective Feedback Item',
        description: 'Creates a feedback item (went well, to improve, or action item) for a sprint retrospective.',
        tags: ['Retrospective'],
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
                name: 'sprint',
                in: 'path',
                required: true,
                description: 'Sprint ID',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type', 'body'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['went_well', 'to_improve', 'action_item'], example: 'went_well'),
                    new OA\Property(property: 'body', type: 'string', example: 'Collaboration was awesome!'),
                    new OA\Property(property: 'assigned_to_user_id', type: 'integer', nullable: true, example: 2)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Feedback item created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/RetroItem')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Sprint not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    public function storeItem(StoreRetroItemRequest $request, Project $project, Sprint $sprint): RetroItemResource
    {
        $this->authorize('createItem', [Retrospective::class, $project]);

        abort_unless($sprint->project_id === $project->id, 404);

        $retro = Retrospective::firstOrCreate(
            ['sprint_id' => $sprint->id],
            ['project_id' => $project->id]
        );

        $item = $retro->items()->create(array_merge($request->validated(), [
            'author_user_id' => $request->user()->id,
            'is_completed' => false,
        ]));

        return new RetroItemResource($item->load(['author', 'assignee']));
    }

    #[OA\Patch(
        path: '/projects/{project}/sprints/{sprint}/retrospective/items/{retroItem}',
        summary: 'Update Retrospective Feedback Item',
        description: 'Updates a feedback item\'s body or details. Users can only update their own items.',
        tags: ['Retrospective'],
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
                name: 'sprint',
                in: 'path',
                required: true,
                description: 'Sprint ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'retroItem',
                in: 'path',
                required: true,
                description: 'Retrospective Item ID',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['went_well', 'to_improve', 'action_item'], example: 'went_well'),
                    new OA\Property(property: 'body', type: 'string', example: 'Collaboration was extremely awesome!'),
                    new OA\Property(property: 'assigned_to_user_id', type: 'integer', nullable: true, example: 2)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Feedback item updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/RetroItem')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project, Sprint or Item not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    public function updateItem(StoreRetroItemRequest $request, Project $project, Sprint $sprint, RetroItem $retroItem): RetroItemResource
    {
        abort_unless($sprint->project_id === $project->id, 404);
        
        $retro = $sprint->retrospective;
        abort_unless($retro && $retroItem->retrospective_id === $retro->id, 404);

        $this->authorize('updateItem', [Retrospective::class, $project, $retroItem]);

        $retroItem->update($request->validated());

        return new RetroItemResource($retroItem->load(['author', 'assignee']));
    }

    #[OA\Delete(
        path: '/projects/{project}/sprints/{sprint}/retrospective/items/{retroItem}',
        summary: 'Delete Retrospective Feedback Item',
        description: 'Deletes a feedback item. Users can only delete their own items.',
        tags: ['Retrospective'],
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
                name: 'sprint',
                in: 'path',
                required: true,
                description: 'Sprint ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'retroItem',
                in: 'path',
                required: true,
                description: 'Retrospective Item ID',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Feedback item deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Retrospective item deleted.')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project, Sprint or Item not found')
        ]
    )]
    public function destroyItem(Project $project, Sprint $sprint, RetroItem $retroItem): JsonResponse
    {
        abort_unless($sprint->project_id === $project->id, 404);

        $retro = $sprint->retrospective;
        abort_unless($retro && $retroItem->retrospective_id === $retro->id, 404);

        $this->authorize('deleteItem', [Retrospective::class, $project, $retroItem]);

        $retroItem->delete();

        return response()->json(['message' => 'Retrospective item deleted.']);
    }
}
