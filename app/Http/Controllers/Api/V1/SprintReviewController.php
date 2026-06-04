<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSprintReviewRequest;
use App\Http\Resources\SprintReviewResource;
use App\Models\BacklogItem;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\SprintReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use OpenApi\Attributes as OA;

class SprintReviewController extends Controller
{
    #[OA\Get(
        path: '/projects/{project}/sprints/{sprint}/review',
        summary: 'Get Sprint Review',
        description: 'Returns the sprint review details for the specified sprint.',
        tags: ['Sprint Review'],
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
                        new OA\Property(property: 'data', ref: '#/components/schemas/SprintReview')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project, Sprint or Review not found')
        ]
    )]
    public function show(Project $project, Sprint $sprint): SprintReviewResource|JsonResponse
    {
        $this->authorize('view', [SprintReview::class, $project]);

        abort_unless($sprint->project_id === $project->id, 404);

        $review = $sprint->sprintReview()
            ->with(['creator', 'items.decider', 'items.backlogItem'])
            ->first();

        if (!$review) {
            return response()->json(['message' => 'Sprint Review not found.'], 404);
        }

        return new SprintReviewResource($review);
    }

    #[OA\Post(
        path: '/projects/{project}/sprints/{sprint}/review',
        summary: 'Submit Sprint Review',
        description: 'Submits or updates the sprint review. Updates backlog item statuses based on the review decisions.',
        tags: ['Sprint Review'],
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
                required: ['summary', 'items'],
                properties: [
                    new OA\Property(property: 'summary', type: 'string', example: 'Finished core features.'),
                    new OA\Property(property: 'demo_url', type: 'string', format: 'uri', nullable: true, example: 'https://example.com/demo'),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'backlog_item_id', type: 'integer', example: 10),
                                new OA\Property(property: 'decision', type: 'string', enum: ['accepted', 'carry_over', 'rejected'], example: 'accepted'),
                                new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'Looking good')
                            ]
                        )
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sprint review saved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/SprintReview')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project or Sprint not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    public function store(StoreSprintReviewRequest $request, Project $project, Sprint $sprint): SprintReviewResource
    {
        $this->authorize('create', [SprintReview::class, $project]);

        abort_unless($sprint->project_id === $project->id, 404);

        if (!in_array($sprint->status, ['active', 'closed'])) {
            throw ValidationException::withMessages([
                'sprint' => ['Sprint reviews can only be submitted for active or closed sprints.'],
            ]);
        }

        $review = DB::transaction(function () use ($request, $project, $sprint) {
            $review = SprintReview::updateOrCreate(
                ['sprint_id' => $sprint->id],
                [
                    'project_id' => $project->id,
                    'summary' => $request->input('summary'),
                    'demo_url' => $request->input('demo_url'),
                    'created_by_user_id' => $request->user()->id,
                ]
            );

            // Clean up existing review items to handle updates
            $review->items()->delete();

            foreach ($request->input('items') as $itemData) {
                $itemId = $itemData['backlog_item_id'];
                $decision = $itemData['decision'];

                // Ensure the item belongs to the sprint
                $exists = $sprint->items()->where('backlog_items.id', $itemId)->exists();
                if (!$exists) {
                    throw ValidationException::withMessages([
                        'items' => ["Backlog item ID {$itemId} does not belong to this sprint."],
                    ]);
                }

                $review->items()->create([
                    'backlog_item_id' => $itemId,
                    'decision' => $decision,
                    'notes' => $itemData['notes'] ?? null,
                    'decided_by_user_id' => $request->user()->id,
                ]);

                // Update backlog item status based on decision
                $backlogItem = BacklogItem::find($itemId);
                if ($decision === 'accepted') {
                    $backlogItem->update([
                        'status' => 'done',
                        'done_at' => now(),
                    ]);
                } else {
                    // carry_over or rejected
                    $backlogItem->update([
                        'status' => 'ready',
                        'done_at' => null,
                    ]);
                }
            }

            return $review;
        });

        return new SprintReviewResource($review->load(['creator', 'items.decider', 'items.backlogItem']));
    }
}
