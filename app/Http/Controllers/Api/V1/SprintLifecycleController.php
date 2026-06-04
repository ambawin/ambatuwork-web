<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SprintResource;
use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

use OpenApi\Attributes as OA;

class SprintLifecycleController extends Controller
{
    #[OA\Post(
        path: '/projects/{project}/sprints/{sprint}/start',
        summary: 'Start Sprint',
        description: 'Starts a planned sprint. The sprint must contain at least one backlog item and the project cannot already have an active sprint.',
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
                description: 'Sprint ID to start',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sprint started successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Sprint')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Sprint not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
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

    #[OA\Post(
        path: '/projects/{project}/sprints/{sprint}/close',
        summary: 'Close Sprint',
        description: 'Closes an active sprint. Unfinished items are moved back to ready.',
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
                description: 'Sprint ID to close',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sprint closed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Sprint closed.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Sprint')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Sprint not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
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