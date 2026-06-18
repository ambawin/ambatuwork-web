<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BacklogItemResource;
use App\Http\Resources\SprintResource;
use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use OpenApi\Attributes as OA;

class SprintBoardController extends Controller
{
    #[OA\Get(
        path: '/projects/{project}/sprints/{sprint}/board',
        summary: 'Get Sprint Board',
        description: 'Returns the sprint board columns and items for the specified sprint.',
        tags: ['Sprint Board'],
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
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'sprint', ref: '#/components/schemas/Sprint'),
                                new OA\Property(
                                    property: 'columns',
                                    properties: [
                                        new OA\Property(property: 'selected', type: 'array', items: new OA\Items(ref: '#/components/schemas/BacklogItem')),
                                        new OA\Property(property: 'in_progress', type: 'array', items: new OA\Items(ref: '#/components/schemas/BacklogItem')),
                                        new OA\Property(property: 'in_review', type: 'array', items: new OA\Items(ref: '#/components/schemas/BacklogItem')),
                                        new OA\Property(property: 'done', type: 'array', items: new OA\Items(ref: '#/components/schemas/BacklogItem'))
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Sprint not found')
        ]
    )]
    public function show(Request $request, Project $project, Sprint $sprint): JsonResponse
    {
        $this->authorize('view', $sprint);

        abort_unless($sprint->project_id === $project->getKey(), 404);

        $items = $sprint->items()->with(['createdBy', 'assignedTo'])->get();

        $groupedItems = [
            'selected' => BacklogItemResource::collection($items->where('status', 'selected'))->resolve($request),
            'in_progress' => BacklogItemResource::collection($items->where('status', 'in_progress'))->resolve($request),
            'in_review' => BacklogItemResource::collection($items->where('status', 'in_review'))->resolve($request),
            'done' => BacklogItemResource::collection($items->where('status', 'done'))->resolve($request),
        ];

        return response()->json([
            'data' => [
                'sprint' => (new SprintResource($sprint->loadCount('items')))->resolve($request),
                'columns' => $groupedItems,
            ],
        ]);
    }
}