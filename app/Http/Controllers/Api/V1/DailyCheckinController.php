<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDailyCheckinRequest;
use App\Http\Resources\DailyCheckinResource;
use App\Models\DailyCheckin;
use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

use OpenApi\Attributes as OA;

class DailyCheckinController extends Controller
{
    #[OA\Get(
        path: '/projects/{project}/sprints/{sprint}/checkins',
        summary: 'List Daily Check-ins',
        description: 'Returns all daily check-ins submitted during the specified sprint.',
        tags: ['Daily Check-ins'],
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
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/DailyCheckin')
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Sprint not found')
        ]
    )]
    public function index(Project $project, Sprint $sprint): AnonymousResourceCollection
    {
        $this->authorize('view', [DailyCheckin::class, $project]);

        abort_unless($sprint->project_id === $project->id, 404);

        $checkins = $sprint->dailyCheckins()
            ->with('user')
            ->orderBy('checkin_date', 'desc')
            ->get();

        return DailyCheckinResource::collection($checkins);
    }

    #[OA\Post(
        path: '/projects/{project}/sprints/{sprint}/checkins',
        summary: 'Submit Daily Check-in',
        description: 'Submits a member\'s daily check-in. If the check-in contains blockers, an Impediment is automatically created.',
        tags: ['Daily Check-ins'],
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
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['confidence_score', 'checkin_date'],
                properties: [
                    new OA\Property(property: 'yesterday', type: 'string', nullable: true, example: 'Implemented tests'),
                    new OA\Property(property: 'today', type: 'string', nullable: true, example: 'Debugging routes'),
                    new OA\Property(property: 'blockers', type: 'string', nullable: true, example: 'Database connection timeout'),
                    new OA\Property(property: 'confidence_score', type: 'integer', minimum: 1, maximum: 5, example: 4),
                    new OA\Property(property: 'checkin_date', type: 'string', format: 'date', example: '2026-06-04')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Check-in submitted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/DailyCheckin')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Sprint not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    public function store(StoreDailyCheckinRequest $request, Project $project, Sprint $sprint): DailyCheckinResource
    {
        $this->authorize('create', [DailyCheckin::class, $project]);

        abort_unless($sprint->project_id === $project->id, 404);

        if ($sprint->status !== 'active') {
            throw ValidationException::withMessages([
                'sprint' => ['You can only submit check-ins for active sprints.'],
            ]);
        }

        $checkin = DailyCheckin::create(array_merge($request->validated(), [
            'project_id' => $project->id,
            'sprint_id' => $sprint->id,
            'user_id' => $request->user()->id,
        ]));

        if ($request->filled('blockers')) {
            $project->impediments()->create([
                'sprint_id' => $sprint->id,
                'reported_by_user_id' => $request->user()->id,
                'title' => 'Blocker reported by ' . $request->user()->name . ' on ' . $checkin->checkin_date->toDateString(),
                'description' => $request->input('blockers'),
                'status' => 'open',
            ]);
        }

        return new DailyCheckinResource($checkin->load('user'));
    }
}
