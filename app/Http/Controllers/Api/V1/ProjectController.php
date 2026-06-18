<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProjectRequest;
use App\Http\Requests\Api\V1\ProjectUpdateRequest;
use App\Http\Resources\ProjectResource;
use App\Models\DefinitionOfDone;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

use OpenApi\Attributes as OA;

class ProjectController extends Controller
{
    #[OA\Get(
        path: '/projects',
        summary: 'List Projects',
        description: 'Returns a list of active projects visible to the authenticated user.',
        tags: ['Projects'],
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
                            items: new OA\Items(ref: '#/components/schemas/Project')
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Project::class);

        $projects = Project::query()
            ->with(['owner', 'activeDefinitionOfDone'])
            ->withCount([
                'memberships as active_memberships_count' => function ($query): void {
                    $query->where('status', 'active');
                },
                'backlogItems as backlog_items_count' => function ($query): void {
                    $query->where('status', '!=', 'archived');
                },
            ])
            ->visibleTo($request->user())
            ->latest('id')
            ->get();

        return ProjectResource::collection($projects);
    }

    #[OA\Post(
        path: '/projects',
        summary: 'Create Project',
        description: 'Creates a new project. Also establishes the creator as the Owner and sets up a default Definition of Done.',
        tags: ['Projects'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'product_goal', 'default_sprint_length_days'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Website Redesign'),
                    new OA\Property(property: 'description', type: 'string', maxLength: 2000, nullable: true, example: 'Refresh the marketing site'),
                    new OA\Property(property: 'product_goal', type: 'string', maxLength: 5000, example: 'Increase demo requests by 20%'),
                    new OA\Property(property: 'default_sprint_length_days', type: 'integer', minimum: 1, maximum: 30, example: 14)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Project created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Project')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    public function store(ProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $project = DB::transaction(function () use ($request): Project {
            $project = Project::create([
                'owner_user_id' => $request->user()->id,
                'name' => $request->string('name')->toString(),
                'description' => $request->input('description'),
                'product_goal' => $request->string('product_goal')->toString(),
                'default_sprint_length_days' => $request->integer('default_sprint_length_days'),
                'status' => 'active',
            ]);

            $project->memberships()->create([
                'user_id' => $request->user()->id,
                'role' => 'owner',
                'status' => 'active',
                'joined_at' => now(),
            ]);

            $project->definitionsOfDone()->create([
                'title' => DefinitionOfDone::defaultTitle(),
                'checklist' => DefinitionOfDone::defaultChecklist(),
                'is_active' => true,
                'created_by_user_id' => $request->user()->id,
            ]);

            return $project->load(['owner', 'activeDefinitionOfDone'])->loadCount([
                'memberships as active_memberships_count' => function ($query): void {
                    $query->where('status', 'active');
                },
                'backlogItems as backlog_items_count' => function ($query): void {
                    $query->where('status', '!=', 'archived');
                },
            ]);
        });

        return (new ProjectResource($project))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/projects/{project}',
        summary: 'Get Project',
        description: 'Returns details of a single project visible to the authenticated user.',
        tags: ['Projects'],
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
                        new OA\Property(property: 'data', ref: '#/components/schemas/Project')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project not found')
        ]
    )]
    public function show(Request $request, Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        $project->load(['owner', 'activeDefinitionOfDone'])->loadCount([
            'memberships as active_memberships_count' => function ($query): void {
                $query->where('status', 'active');
            },
            'backlogItems as backlog_items_count' => function ($query): void {
                $query->where('status', '!=', 'archived');
            },
        ]);

        return new ProjectResource($project);
    }

    #[OA\Patch(
        path: '/projects/{project}',
        summary: 'Update Project',
        description: 'Updates project settings. All fields in request are optional.',
        tags: ['Projects'],
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
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Website Refresh'),
                    new OA\Property(property: 'description', type: 'string', maxLength: 2000, nullable: true, example: 'New refresh'),
                    new OA\Property(property: 'product_goal', type: 'string', maxLength: 5000, example: 'New product goal'),
                    new OA\Property(property: 'default_sprint_length_days', type: 'integer', minimum: 1, maximum: 30, example: 14),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'archived'], example: 'active')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Project updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Project')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    public function update(ProjectUpdateRequest $request, Project $project): ProjectResource
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        $project->load(['owner', 'activeDefinitionOfDone'])->loadCount([
            'memberships as active_memberships_count' => function ($query): void {
                $query->where('status', 'active');
            },
            'backlogItems as backlog_items_count' => function ($query): void {
                $query->where('status', '!=', 'archived');
            },
        ]);

        return new ProjectResource($project);
    }
}