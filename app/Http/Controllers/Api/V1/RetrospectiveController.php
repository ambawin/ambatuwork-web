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

class RetrospectiveController extends Controller
{
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

    public function updateItem(StoreRetroItemRequest $request, Project $project, Sprint $sprint, RetroItem $retroItem): RetroItemResource
    {
        abort_unless($sprint->project_id === $project->id, 404);
        
        $retro = $sprint->retrospective;
        abort_unless($retro && $retroItem->retrospective_id === $retro->id, 404);

        $this->authorize('updateItem', [Retrospective::class, $project, $retroItem]);

        $retroItem->update($request->validated());

        return new RetroItemResource($retroItem->load(['author', 'assignee']));
    }

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
