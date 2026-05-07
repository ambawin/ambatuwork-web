<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SprintResource;
use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SprintLifecycleController extends Controller
{
    public function start(Request $request, Project $project, Sprint $sprint): SprintResource
    {
        $this->authorize('start', $sprint);

        abort_unless($sprint->project_id === $project->getKey(), 404);

        if ($sprint->status !== 'planned') {
            throw ValidationException::withMessages([
                'sprint' => ['Only planned sprints can be started.'],
            ]);
        }

        if ($project->sprints()->where('status', 'active')->whereKeyNot($sprint->getKey())->exists()) {
            throw ValidationException::withMessages([
                'sprint' => ['Only one active sprint is allowed per project.'],
            ]);
        }

        if (! $sprint->items()->exists()) {
            throw ValidationException::withMessages([
                'sprint' => ['A sprint must have at least one backlog item before it can start.'],
            ]);
        }

        $sprint->update([
            'status' => 'active',
        ]);

        return new SprintResource($sprint->refresh()->loadCount('items'));
    }

    public function close(Request $request, Project $project, Sprint $sprint): JsonResponse
    {
        $this->authorize('close', $sprint);

        abort_unless($sprint->project_id === $project->getKey(), 404);

        if ($sprint->status !== 'active') {
            throw ValidationException::withMessages([
                'sprint' => ['Only active sprints can be closed.'],
            ]);
        }

        $unfinishedItemIds = $sprint->items()
            ->where('status', '!=', 'done')
            ->pluck('backlog_items.id');

        $sprint->items()
            ->whereIn('backlog_items.id', $unfinishedItemIds)
            ->update(['status' => 'ready']);

        $sprint->update([
            'status' => 'closed',
            'closed_by_user_id' => $request->user()->id,
            'closed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Sprint closed.',
            'data' => (new SprintResource($sprint->refresh()->loadCount('items')))->resolve($request),
        ]);
    }
}