<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImpedimentRequest;
use App\Http\Resources\ImpedimentResource;
use App\Jobs\SendFcmNotificationJob;
use App\Models\Impediment;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

use OpenApi\Attributes as OA;

class ImpedimentController extends Controller
{
    #[OA\Get(
        path: '/projects/{project}/impediments',
        summary: 'List Impediments',
        description: 'Returns all impediments (blockers) for the specified project, newest first.',
        tags: ['Impediments'],
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
                            items: new OA\Items(ref: '#/components/schemas/Impediment')
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project not found')
        ]
    )]
    public function index(Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', [Impediment::class, $project]);

        $impediments = $project->impediments()
            ->with(['reporter', 'owner'])
            ->orderBy('created_at', 'desc')
            ->get();

        return ImpedimentResource::collection($impediments);
    }

    #[OA\Post(
        path: '/projects/{project}/impediments',
        summary: 'Create Impediment',
        description: 'Creates a new impediment/blocker for the project. Automatically associates it with the active sprint if one exists.',
        tags: ['Impediments'],
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
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Slow local development build'),
                    new OA\Property(property: 'description', type: 'string', maxLength: 2000, nullable: true, example: 'Webpack compilation takes more than 30s'),
                    new OA\Property(property: 'status', type: 'string', enum: ['open', 'in_progress', 'resolved', 'ignored'], default: 'open', example: 'open')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Impediment created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Impediment')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    public function store(StoreImpedimentRequest $request, Project $project): ImpedimentResource
    {
        $this->authorize('create', [Impediment::class, $project]);

        $activeSprint = $project->activeSprint;

        $impediment = $project->impediments()->create(array_merge($request->validated(), [
            'sprint_id' => $activeSprint?->id,
            'reported_by_user_id' => $request->user()->id,
            'status' => 'open',
        ]));

        $recipients = [];
        if ($project->owner_user_id !== $request->user()->id) {
            $recipients[] = $project->owner_user_id;
        }

        $supervisors = $project->members()
            ->wherePivot('role', 'supervisor')
            ->whereKeyNot($request->user()->id)
            ->pluck('users.id')
            ->all();
        $recipients = array_merge($recipients, $supervisors);

        if ($impediment->backlog_item_id !== null) {
            $backlogItem = $impediment->backlogItem;
            if ($backlogItem && $backlogItem->assigned_to_user_id !== null && $backlogItem->assigned_to_user_id !== $request->user()->id) {
                $recipients[] = $backlogItem->assigned_to_user_id;
            }
        }

        $recipients = array_unique($recipients);

        foreach ($recipients as $userId) {
            SendFcmNotificationJob::dispatch(
                userId: $userId,
                title: "Blocker reported in {$project->name}",
                body: "{$request->user()->name} reported: {$impediment->title}",
                data: [
                    'type' => 'impediment_reported',
                    'project_id' => (string) $project->id,
                    'impediment_id' => (string) $impediment->id,
                ],
            );
        }

        return new ImpedimentResource($impediment->load(['reporter', 'owner']));
    }

    #[OA\Patch(
        path: '/projects/{project}/impediments/{impediment}',
        summary: 'Update Impediment',
        description: 'Updates an impediment\'s details or status.',
        tags: ['Impediments'],
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
                name: 'impediment',
                in: 'path',
                required: true,
                description: 'Impediment ID',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Slow dev build - updated'),
                    new OA\Property(property: 'description', type: 'string', maxLength: 2000, nullable: true, example: 'Updated description'),
                    new OA\Property(property: 'status', type: 'string', enum: ['open', 'in_progress', 'resolved', 'ignored'], example: 'in_progress')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Impediment updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Impediment')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Impediment not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    public function update(StoreImpedimentRequest $request, Project $project, Impediment $impediment): ImpedimentResource
    {
        $this->authorize('update', [Impediment::class, $project]);

        abort_unless($impediment->project_id === $project->id, 404);

        $data = $request->validated();

        if (isset($data['status'])) {
            if ($data['status'] === 'resolved' && $impediment->status !== 'resolved') {
                $data['resolved_at'] = now();
            } elseif ($data['status'] !== 'resolved') {
                $data['resolved_at'] = null;
            }
        }

        $impediment->update($data);

        if ($impediment->wasChanged('status') && $impediment->status === 'resolved' && $impediment->reported_by_user_id !== $request->user()->id) {
            SendFcmNotificationJob::dispatch(
                userId: $impediment->reported_by_user_id,
                title: "Blocker resolved in {$project->name}",
                body: "Your reported blocker \"{$impediment->title}\" has been resolved.",
                data: [
                    'type' => 'impediment_resolved',
                    'project_id' => (string) $project->id,
                    'impediment_id' => (string) $impediment->id,
                ],
            );
        }

        return new ImpedimentResource($impediment->load(['reporter', 'owner']));
    }

    #[OA\Post(
        path: '/projects/{project}/impediments/{impediment}/resolve',
        summary: 'Resolve Impediment',
        description: 'Marks an impediment as resolved.',
        tags: ['Impediments'],
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
                name: 'impediment',
                in: 'path',
                required: true,
                description: 'Impediment ID',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Impediment resolved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Impediment')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Impediment not found')
        ]
    )]
    public function resolve(Request $request, Project $project, Impediment $impediment): ImpedimentResource
    {
        $this->authorize('update', [Impediment::class, $project]);

        abort_unless($impediment->project_id === $project->id, 404);

        $impediment->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        if ($impediment->wasChanged('status') && $impediment->reported_by_user_id !== $request->user()->id) {
            SendFcmNotificationJob::dispatch(
                userId: $impediment->reported_by_user_id,
                title: "Blocker resolved in {$project->name}",
                body: "Your reported blocker \"{$impediment->title}\" has been resolved.",
                data: [
                    'type' => 'impediment_resolved',
                    'project_id' => (string) $project->id,
                    'impediment_id' => (string) $impediment->id,
                ],
            );
        }

        return new ImpedimentResource($impediment->load(['reporter', 'owner']));
    }
}
