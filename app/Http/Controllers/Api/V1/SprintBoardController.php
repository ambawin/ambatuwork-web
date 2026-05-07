<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BacklogItemResource;
use App\Http\Resources\SprintResource;
use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SprintBoardController extends Controller
{
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