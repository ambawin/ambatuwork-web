<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePeerReviewRequest;
use App\Http\Resources\PeerReviewCycleResource;
use App\Http\Resources\PeerReviewResource;
use App\Jobs\SendFcmNotificationJob;
use App\Models\PeerReview;
use App\Models\PeerReviewCycle;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

use OpenApi\Attributes as OA;

class PeerReviewController extends Controller
{
    #[OA\Get(
        path: '/projects/{project}/sprints/{sprint}/peer-review-cycle',
        summary: 'Get Peer Review Cycle details',
        description: 'Returns the peer review cycle details for the specified sprint.',
        tags: ['Peer Reviews'],
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
                        new OA\Property(property: 'data', ref: '#/components/schemas/PeerReviewCycle')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project, Sprint or Cycle not found')
        ]
    )]
    public function showCycle(Project $project, Sprint $sprint): PeerReviewCycleResource|JsonResponse
    {
        $this->authorize('viewCycle', [PeerReviewCycle::class, $project]);

        abort_unless($sprint->project_id === $project->id, 404);

        $cycle = $sprint->peerReviewCycle;
        if (!$cycle) {
            return response()->json(['message' => 'Peer Review Cycle not found.'], 404);
        }

        return new PeerReviewCycleResource($cycle);
    }

    #[OA\Post(
        path: '/projects/{project}/sprints/{sprint}/peer-review-cycle',
        summary: 'Create/Open Peer Review Cycle',
        description: 'Creates and opens a peer review cycle for a sprint.',
        tags: ['Peer Reviews'],
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
                response: 201,
                description: 'Cycle created/opened successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/PeerReviewCycle')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Sprint not found')
        ]
    )]
    public function storeCycle(Request $request, Project $project, Sprint $sprint): PeerReviewCycleResource
    {
        $this->authorize('manageCycle', [PeerReviewCycle::class, $project]);

        abort_unless($sprint->project_id === $project->id, 404);

        $cycle = PeerReviewCycle::firstOrCreate(
            ['sprint_id' => $sprint->id],
            [
                'project_id' => $project->id,
                'status' => 'open',
                'opens_at' => now(),
            ]
        );

        if ($cycle->wasRecentlyCreated) {
            $members = $project->members()
                ->wherePivot('role', '!=', 'supervisor')
                ->whereKeyNot($request->user()->id)
                ->get();

            foreach ($members as $member) {
                SendFcmNotificationJob::dispatch(
                    userId: $member->id,
                    title: "Peer Review open for Sprint {$sprint->name}",
                    body: "The peer review cycle is now open. Please submit reviews for your peers.",
                    data: [
                        'type' => 'peer_review_cycle_opened',
                        'project_id' => (string) $project->id,
                        'cycle_id' => (string) $cycle->id,
                    ],
                );
            }
        }

        return new PeerReviewCycleResource($cycle);
    }

    #[OA\Post(
        path: '/projects/{project}/peer-review-cycles/{cycle}/reviews',
        summary: 'Submit Peer Review',
        description: 'Submits a peer review for another user in the open cycle.',
        tags: ['Peer Reviews'],
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
                name: 'cycle',
                in: 'path',
                required: true,
                description: 'Peer Review Cycle ID',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['reviewee_user_id', 'collaboration_score', 'delivery_score', 'communication_score'],
                properties: [
                    new OA\Property(property: 'reviewee_user_id', type: 'integer', example: 1),
                    new OA\Property(property: 'collaboration_score', type: 'integer', minimum: 1, maximum: 5, example: 5),
                    new OA\Property(property: 'delivery_score', type: 'integer', minimum: 1, maximum: 5, example: 4),
                    new OA\Property(property: 'communication_score', type: 'integer', minimum: 1, maximum: 5, example: 5),
                    new OA\Property(property: 'continue_feedback', type: 'string', nullable: true, example: 'Good planning.'),
                    new OA\Property(property: 'improve_feedback', type: 'string', nullable: true, example: 'None.')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Peer review submitted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/PeerReview')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Cycle not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    public function submitReview(StorePeerReviewRequest $request, Project $project, PeerReviewCycle $cycle): PeerReviewResource
    {
        $this->authorize('submitReview', [PeerReviewCycle::class, $project]);

        abort_unless($cycle->project_id === $project->id, 404);

        if ($cycle->status !== 'open') {
            throw ValidationException::withMessages([
                'cycle' => ['This peer review cycle is closed.'],
            ]);
        }

        // Validate reviewer role
        $reviewerRole = $project->roleFor($request->user());
        if ($reviewerRole === 'supervisor' && !$project->isOwnedBy($request->user())) {
            throw ValidationException::withMessages([
                'reviewer' => ['Supervisors cannot submit peer reviews.'],
            ]);
        }

        // Validate reviewee
        $reviewee = User::find($request->input('reviewee_user_id'));
        if (!$project->isAccessibleTo($reviewee)) {
            throw ValidationException::withMessages([
                'reviewee_user_id' => ['The reviewee must be a member of the project.'],
            ]);
        }

        $revieweeRole = $project->roleFor($reviewee);
        if ($revieweeRole === 'supervisor') {
            throw ValidationException::withMessages([
                'reviewee_user_id' => ['Supervisors cannot be reviewed.'],
            ]);
        }

        $review = PeerReview::updateOrCreate(
            [
                'peer_review_cycle_id' => $cycle->id,
                'reviewer_user_id' => $request->user()->id,
                'reviewee_user_id' => $reviewee->id,
            ],
            array_merge($request->validated(), [
                'submitted_at' => now(),
            ])
        );

        return new PeerReviewResource($review->load(['reviewer', 'reviewee']));
    }

    #[OA\Post(
        path: '/projects/{project}/peer-review-cycles/{cycle}/close',
        summary: 'Close Peer Review Cycle',
        description: 'Closes an open peer review cycle.',
        tags: ['Peer Reviews'],
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
                name: 'cycle',
                in: 'path',
                required: true,
                description: 'Cycle ID to close',
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cycle closed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/PeerReviewCycle')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Cycle not found')
        ]
    )]
    public function closeCycle(Request $request, Project $project, PeerReviewCycle $cycle): PeerReviewCycleResource
    {
        $this->authorize('manageCycle', [PeerReviewCycle::class, $project]);

        abort_unless($cycle->project_id === $project->id, 404);

        $cycle->update([
            'status' => 'closed',
            'closes_at' => now(),
        ]);

        if ($cycle->wasChanged('status') && $cycle->status === 'closed') {
            $members = $project->members()
                ->wherePivot('role', '!=', 'supervisor')
                ->whereKeyNot($request->user()->id)
                ->get();

            foreach ($members as $member) {
                SendFcmNotificationJob::dispatch(
                    userId: $member->id,
                    title: "Peer Review results ready in {$project->name}",
                    body: "The peer review cycle has closed. Your feedback summary is now available.",
                    data: [
                        'type' => 'peer_review_cycle_closed',
                        'project_id' => (string) $project->id,
                        'cycle_id' => (string) $cycle->id,
                    ],
                );
            }
        }

        return new PeerReviewCycleResource($cycle);
    }

    #[OA\Get(
        path: '/projects/{project}/peer-review-cycles/{cycle}/summary',
        summary: 'Get Peer Review Cycle Summary (Owners)',
        description: 'Returns the full aggregated peer review summary for all active project members. Restricted to project owners.',
        tags: ['Peer Reviews'],
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
                name: 'cycle',
                in: 'path',
                required: true,
                description: 'Cycle ID',
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
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(
                                        property: 'user',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 2),
                                            new OA\Property(property: 'name', type: 'string', example: 'Sam Example'),
                                            new OA\Property(property: 'avatar_url', type: 'string', format: 'uri', nullable: true, example: null)
                                        ]
                                    ),
                                    new OA\Property(property: 'review_count', type: 'integer', example: 1),
                                    new OA\Property(property: 'avg_collaboration_score', type: 'number', format: 'float', example: 5),
                                    new OA\Property(property: 'avg_delivery_score', type: 'number', format: 'float', example: 4),
                                    new OA\Property(property: 'avg_communication_score', type: 'number', format: 'float', example: 5),
                                    new OA\Property(
                                        property: 'feedbacks',
                                        type: 'array',
                                        items: new OA\Items(
                                            properties: [
                                                new OA\Property(property: 'continue', type: 'string', example: 'Good planning.'),
                                                new OA\Property(property: 'improve', type: 'string', example: 'None.')
                                            ]
                                        )
                                    )
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Cycle not found')
        ]
    )]
    public function summary(Project $project, PeerReviewCycle $cycle): JsonResponse
    {
        $this->authorize('viewSummary', [PeerReviewCycle::class, $project]);

        abort_unless($cycle->project_id === $project->id, 404);

        $activeMembers = $project->members()
            ->wherePivot('role', '!=', 'supervisor')
            ->get();

        $summary = [];
        foreach ($activeMembers as $member) {
            $reviews = $cycle->reviews()->where('reviewee_user_id', $member->id)->get();
            $count = $reviews->count();
            $avgCollab = $count > 0 ? round($reviews->avg('collaboration_score'), 2) : null;
            $avgDelivery = $count > 0 ? round($reviews->avg('delivery_score'), 2) : null;
            $avgComm = $count > 0 ? round($reviews->avg('communication_score'), 2) : null;

            $feedbacks = $reviews->map(function ($r) {
                return [
                    'continue' => $r->continue_feedback,
                    'improve' => $r->improve_feedback,
                ];
            })->filter(fn($f) => !empty($f['continue']) || !empty($f['improve']))->values();

            $summary[] = [
                'user' => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'avatar_url' => $member->avatar_url,
                ],
                'review_count' => $count,
                'avg_collaboration_score' => $avgCollab,
                'avg_delivery_score' => $avgDelivery,
                'avg_communication_score' => $avgComm,
                'feedbacks' => $feedbacks,
            ];
        }

        return response()->json(['data' => $summary]);
    }

    #[OA\Get(
        path: '/projects/{project}/peer-review-cycles/{cycle}/my-summary',
        summary: 'Get My Peer Review Summary (Anonymous & Aggregated)',
        description: 'Returns the current user\'s aggregated anonymous peer reviews.',
        tags: ['Peer Reviews'],
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
                name: 'cycle',
                in: 'path',
                required: true,
                description: 'Cycle ID',
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
                            properties: [
                                new OA\Property(
                                    property: 'user',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 2),
                                        new OA\Property(property: 'name', type: 'string', example: 'Sam Example')
                                    ]
                                ),
                                new OA\Property(property: 'review_count', type: 'integer', example: 0),
                                new OA\Property(property: 'avg_collaboration_score', type: 'number', format: 'float', nullable: true, example: null),
                                new OA\Property(property: 'avg_delivery_score', type: 'number', format: 'float', nullable: true, example: null),
                                new OA\Property(property: 'avg_communication_score', type: 'number', format: 'float', nullable: true, example: null),
                                new OA\Property(
                                    property: 'feedbacks',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'continue', type: 'string', example: 'Good planning.'),
                                            new OA\Property(property: 'improve', type: 'string', example: 'None.')
                                        ]
                                    )
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Cycle not found')
        ]
    )]
    public function mySummary(Request $request, Project $project, PeerReviewCycle $cycle): JsonResponse
    {
        $this->authorize('viewMySummary', [PeerReviewCycle::class, $project]);

        abort_unless($cycle->project_id === $project->id, 404);

        $user = $request->user();
        $reviews = $cycle->reviews()->where('reviewee_user_id', $user->id)->get();
        $count = $reviews->count();
        $avgCollab = $count > 0 ? round($reviews->avg('collaboration_score'), 2) : null;
        $avgDelivery = $count > 0 ? round($reviews->avg('delivery_score'), 2) : null;
        $avgComm = $count > 0 ? round($reviews->avg('communication_score'), 2) : null;

        $feedbacks = $reviews->map(function ($r) {
            return [
                'continue' => $r->continue_feedback,
                'improve' => $r->improve_feedback,
            ];
        })->filter(fn($f) => !empty($f['continue']) || !empty($f['improve']))->values();

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'review_count' => $count,
                'avg_collaboration_score' => $avgCollab,
                'avg_delivery_score' => $avgDelivery,
                'avg_communication_score' => $avgComm,
                'feedbacks' => $feedbacks,
            ]
        ]);
    }
}
