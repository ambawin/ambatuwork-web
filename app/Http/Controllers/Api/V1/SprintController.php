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

class SprintController extends Controller
{
    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        $sprints = $project->sprints()
            ->withCount('items')
            ->latest('id')
            ->get();

        return SprintResource::collection($sprints);
    }

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

    public function show(Request $request, Project $project, Sprint $sprint): SprintResource
    {
        $this->authorize('view', $sprint);

        abort_unless($sprint->project_id === $project->getKey(), 404);

        return new SprintResource($sprint->loadCount('items'));
    }
}