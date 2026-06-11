<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FcmTokenRequest;
use App\Http\Resources\UserStatsResource;
use App\Models\BacklogItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[OA\Get(
        path: '/users/me/stats',
        summary: 'Get Authenticated User Statistics',
        description: 'Returns workspace and activity statistics for the currently logged in user.',
        tags: ['User Statistics'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User Statistics',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/UserStats')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function stats(Request $request): UserStatsResource
    {
        $user = $request->user();

        // 1. Projects
        $totalActiveProjects = $user->projects()->count();

        // 2. Backlog Items
        $assignedBacklogItemsQuery = BacklogItem::query()
            ->where('assigned_to_user_id', $user->id)
            ->where('status', '!=', 'archived');
        
        $assignedTotal = (clone $assignedBacklogItemsQuery)->count();
        
        $assignedByStatusCounts = (clone $assignedBacklogItemsQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $completedPoints = (clone $assignedBacklogItemsQuery)
            ->where('status', 'done')
            ->sum('estimate_points');

        // 3. Daily Check-ins
        $totalCheckins = $user->dailyCheckins()->count();
        $averageConfidence = $user->dailyCheckins()->avg('confidence_score') ?? 0.0;

        // 4. Impediments
        $reportedImpedimentsQuery = $user->reportedImpediments();
        $reportedTotal = (clone $reportedImpedimentsQuery)->count();
        $reportedResolved = (clone $reportedImpedimentsQuery)->where('status', 'resolved')->count();
        
        $reportedByStatusCounts = (clone $reportedImpedimentsQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // 5. Peer Reviews
        $submittedTotal = $user->submittedPeerReviews()->count();
        $receivedReviewsQuery = $user->receivedPeerReviews();
        $receivedTotal = (clone $receivedReviewsQuery)->count();
        $receivedAverageScores = [
            'collaboration' => (clone $receivedReviewsQuery)->avg('collaboration_score') ?? 0.0,
            'delivery' => (clone $receivedReviewsQuery)->avg('delivery_score') ?? 0.0,
            'communication' => (clone $receivedReviewsQuery)->avg('communication_score') ?? 0.0,
        ];

        return new UserStatsResource([
            'projects' => [
                'total_active' => $totalActiveProjects,
            ],
            'backlog_items' => [
                'assigned_total' => $assignedTotal,
                'assigned_by_status' => $assignedByStatusCounts,
                'completed_points' => $completedPoints,
            ],
            'daily_checkins' => [
                'total_submitted' => $totalCheckins,
                'average_confidence' => round($averageConfidence, 2),
            ],
            'impediments' => [
                'reported_total' => $reportedTotal,
                'reported_resolved' => $reportedResolved,
                'reported_by_status' => $reportedByStatusCounts,
            ],
            'peer_reviews' => [
                'submitted_total' => $submittedTotal,
                'received_total' => $receivedTotal,
                'received_average_scores' => $receivedAverageScores,
            ],
        ]);
    }
    #[OA\Put(
        path: '/auth/device-token',
        summary: 'Register FCM Device Token',
        description: 'Registers or updates an FCM device token for the authenticated user. Call this on every app launch after obtaining a fresh FCM token from the Firebase SDK.',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['fcm_token'],
                properties: [
                    new OA\Property(property: 'fcm_token', type: 'string', maxLength: 500, example: 'eKxi2...'),
                    new OA\Property(property: 'device_name', type: 'string', maxLength: 255, example: 'Pixel 9 Pro', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Device token registered'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function registerFcmToken(FcmTokenRequest $request): JsonResponse
    {
        $request->user()->deviceTokens()->updateOrCreate(
            ['fcm_token' => $request->string('fcm_token')->toString()],
            ['device_name' => $request->string('device_name')->toString() ?: null],
        );

        return response()->json(['message' => 'Device token registered.']);
    }
}
