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

class SprintReviewController extends Controller
{
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
