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

class ProjectBacklogItemController extends Controller
{
    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        $backlogItems = $project->backlogItems()
            ->where('status', '!=', 'archived')
            ->with(['createdBy', 'assignedTo'])
            ->orderByRaw('CASE WHEN priority_rank IS NULL THEN 1 ELSE 0 END, priority_rank ASC, id ASC')
            ->get();

        return BacklogItemResource::collection($backlogItems);
    }

    public function store(ProjectBacklogItemRequest $request, Project $project): JsonResponse
    {
        $this->authorize('manageBacklog', $project);

        $validated = $request->validated();

        $backlogItem = $project->backlogItems()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? 'story',
            'status' => 'backlog',
            'priority_rank' => $validated['priority_rank'] ?? $this->nextPriorityRank($project),
            'business_value' => $validated['business_value'] ?? null,
            'estimate_points' => $validated['estimate_points'] ?? null,
            'acceptance_criteria' => $validated['acceptance_criteria'] ?? null,
            'created_by_user_id' => $request->user()->id,
            'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
        ])->load(['createdBy', 'assignedTo']);

        return (new BacklogItemResource($backlogItem))->response()->setStatusCode(201);
    }

    public function show(Request $request, Project $project, BacklogItem $backlogItem): BacklogItemResource
    {
        $this->authorize('view', $project);

        abort_unless($backlogItem->project_id === $project->getKey(), 404);

        return new BacklogItemResource($backlogItem->load(['createdBy', 'assignedTo']));
    }

    public function update(ProjectBacklogItemUpdateRequest $request, Project $project, BacklogItem $backlogItem): JsonResponse
    {
        $this->authorize('manageBacklog', $project);

        abort_unless($backlogItem->project_id === $project->getKey(), 404);

        $validated = $request->validated();

        $backlogItem->fill($validated)->save();

        return (new BacklogItemResource($backlogItem->fresh()->load(['createdBy', 'assignedTo'])))->response();
    }

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

    private function nextPriorityRank(Project $project): float
    {
        $maxPriorityRank = (float) ($project->backlogItems()->max('priority_rank') ?? 0);

        return $maxPriorityRank + 1;
    }
}