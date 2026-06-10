<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SprintRequest;
use App\Http\Resources\BacklogItemResource;
use App\Http\Resources\SprintResource;
use App\Models\BacklogItem;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\SprintItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use OpenApi\Attributes as OA;

class SprintController extends Controller
{
    #[OA\Get(
        path: '/projects/{project}/sprints',
        summary: 'List Project Sprints',
        description: 'Returns a list of all sprints associated with the specified project, newest first.',
        tags: ['Sprints'],
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
                            items: new OA\Items(ref: '#/components/schemas/Sprint')
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

        $sprints = $project->sprints()
            ->withCount('items')
            ->latest('id')
            ->get();

        return SprintResource::collection($sprints);
    }

    #[OA\Post(
        path: '/projects/{project}/sprints',
        summary: 'Create Sprint',
        description: 'Creates a planned sprint and commits the selected backlog items into it. Only one active sprint is allowed per project. The duration of the sprint (end_date - start_date) cannot exceed the project’s default_sprint_length_days setting.',
        tags: ['Sprints'],
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
                required: ['name', 'sprint_goal', 'start_date', 'end_date', 'backlog_item_ids'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Sprint 12'),
                    new OA\Property(property: 'sprint_goal', type: 'string', maxLength: 5000, example: 'Ship the invitation flow'),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-06-01'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2026-06-14'),
                    new OA\Property(
                        property: 'backlog_item_ids',
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        example: [10, 11, 12]
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Sprint created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Sprint')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    public function store(SprintRequest $request, Project $project): JsonResponse
    {
        $this->authorize('create', [Sprint::class, $project]);

        if ($project->sprints()->where('status', 'active')->exists()) {
            throw ValidationException::withMessages([
                'sprint' => ['Only one active sprint is allowed per project.'],
            ]);
        }

        $validated = $request->validated();
        $backlogItems = BacklogItem::query()
            ->where('project_id', $project->id)
            ->whereIn('id', $validated['backlog_item_ids'])
            ->where('status', '!=', 'archived')
            ->get();

        if ($backlogItems->count() !== count(array_unique($validated['backlog_item_ids']))) {
            throw ValidationException::withMessages([
                'backlog_item_ids' => ['One or more backlog items are invalid for this project.'],
            ]);
        }

        $sprint = DB::transaction(function () use ($project, $request, $validated, $backlogItems): Sprint {
            $sprint = $project->sprints()->create([
                'name' => $validated['name'],
                'sprint_goal' => $validated['sprint_goal'],
                'status' => 'planned',
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'created_by_user_id' => $request->user()->id,
            ]);

            foreach ($backlogItems as $backlogItem) {
                SprintItem::create([
                    'sprint_id' => $sprint->id,
                    'backlog_item_id' => $backlogItem->id,
                    'committed_points' => $backlogItem->estimate_points,
                    'added_by_user_id' => $request->user()->id,
                    'added_at' => now(),
                ]);

                $backlogItem->update([
                    'status' => 'selected',
                ]);
            }

            return $sprint->loadCount('items');
        });

        return (new SprintResource($sprint))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/projects/{project}/sprints/{sprint}',
        summary: 'Get Sprint details',
        description: 'Returns details of a single sprint.',
        tags: ['Sprints'],
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
                        new OA\Property(property: 'data', ref: '#/components/schemas/Sprint')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Sprint not found')
        ]
    )]
    public function show(Request $request, Project $project, Sprint $sprint): SprintResource
    {
        $this->authorize('view', $sprint);

        abort_unless($sprint->project_id === $project->getKey(), 404);

        return new SprintResource($sprint->loadCount('items'));
    }
}