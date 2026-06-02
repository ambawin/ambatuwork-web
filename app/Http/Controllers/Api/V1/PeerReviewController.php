<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePeerReviewRequest;
use App\Http\Resources\PeerReviewCycleResource;
use App\Http\Resources\PeerReviewResource;
use App\Models\PeerReview;
use App\Models\PeerReviewCycle;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PeerReviewController extends Controller
{
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

    public function storeCycle(Project $project, Sprint $sprint): PeerReviewCycleResource
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

        return new PeerReviewCycleResource($cycle);
    }

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

    public function closeCycle(Project $project, PeerReviewCycle $cycle): PeerReviewCycleResource
    {
        $this->authorize('manageCycle', [PeerReviewCycle::class, $project]);

        abort_unless($cycle->project_id === $project->id, 404);

        $cycle->update([
            'status' => 'closed',
            'closes_at' => now(),
        ]);

        return new PeerReviewCycleResource($cycle);
    }

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
