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

class ProjectController extends Controller
{
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
                'wip_limit_per_member' => $request->integer('wip_limit_per_member') ?: null,
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