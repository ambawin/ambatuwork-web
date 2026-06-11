<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectStatsResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class ProjectStatsController extends Controller
{
    #[OA\Get(
        path: '/projects/{project}/stats',
        summary: 'Get Project Statistics',
        description: 'Returns detailed statistics for the specified project, visible only to authorized project members.',
        tags: ['Project Statistics'],
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
                description: 'Project Statistics',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/ProjectStats')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Project not found')
        ]
    )]
    public function show(Request $request, Project $project): ProjectStatsResource
    {
        $this->authorize('view', $project);

        // 1. Members stats
        $membersQuery = $project->memberships()->where('status', 'active');
        $totalMembers = (clone $membersQuery)->count();
        $membersByRole = (clone $membersQuery)
            ->select('role', DB::raw('count(*) as count'))
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();

        // 2. Sprints stats
        $sprintsQuery = $project->sprints();
        $totalSprints = (clone $sprintsQuery)->count();
        $activeSprints = (clone $sprintsQuery)->where('status', 'active')->count();
        $completedSprints = (clone $sprintsQuery)->where('status', 'closed')->count();

        // Average Velocity (sum of committed points for 'done' items in closed sprints, divided by count of closed sprints)
        $averageVelocity = 0.0;
        if ($completedSprints > 0) {
            $totalCompletedPoints = DB::table('sprints')
                ->join('sprint_items', 'sprints.id', '=', 'sprint_items.sprint_id')
                ->join('backlog_items', 'sprint_items.backlog_item_id', '=', 'backlog_items.id')
                ->where('sprints.project_id', $project->id)
                ->where('sprints.status', 'closed')
                ->where('backlog_items.status', 'done')
                ->sum('sprint_items.committed_points');
            
            $averageVelocity = round((float) $totalCompletedPoints / $completedSprints, 2);
        }

        // 3. Backlog Items stats
        $backlogQuery = $project->backlogItems()->where('status', '!=', 'archived');
        $totalBacklogItems = (clone $backlogQuery)->count();
        $totalPoints = (clone $backlogQuery)->sum('estimate_points');
        $completedPoints = (clone $backlogQuery)->where('status', 'done')->sum('estimate_points');
        $backlogByStatusCounts = (clone $backlogQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // 4. Daily Check-ins stats
        $totalCheckins = $project->dailyCheckins()->count();
        $averageConfidence = $project->dailyCheckins()->avg('confidence_score') ?? 0.0;

        // 5. Impediments stats
        $impedimentsQuery = $project->impediments();
        $totalImpediments = (clone $impedimentsQuery)->count();
        $resolvedImpediments = (clone $impedimentsQuery)->where('status', 'resolved')->count();
        $impedimentsByStatusCounts = (clone $impedimentsQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // 6. Retrospectives stats
        $retrospectivesQuery = $project->retrospectives();
        $totalRetrospectives = (clone $retrospectivesQuery)->count();
        $averageHappiness = $project->retrospectives()->avg('team_happiness_score') ?? 0.0;

        // 7. Peer Reviews stats
        $totalCycles = $project->peerReviewCycles()->count();
        $peerReviewsQuery = DB::table('peer_reviews')
            ->join('peer_review_cycles', 'peer_reviews.peer_review_cycle_id', '=', 'peer_review_cycles.id')
            ->where('peer_review_cycles.project_id', $project->id);
        
        $averageScores = [
            'collaboration' => $peerReviewsQuery->avg('collaboration_score') ?? 0.0,
            'delivery' => $peerReviewsQuery->avg('delivery_score') ?? 0.0,
            'communication' => $peerReviewsQuery->avg('communication_score') ?? 0.0,
        ];

        return new ProjectStatsResource([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
            ],
            'members' => [
                'total' => $totalMembers,
                'by_role' => [
                    'owner' => $membersByRole['owner'] ?? 0,
                    'member' => $membersByRole['member'] ?? 0,
                    'supervisor' => $membersByRole['supervisor'] ?? 0,
                ],
            ],
            'sprints' => [
                'total' => $totalSprints,
                'active' => $activeSprints,
                'completed' => $completedSprints,
                'average_velocity' => $averageVelocity,
            ],
            'backlog_items' => [
                'total' => $totalBacklogItems,
                'total_points' => (int) $totalPoints,
                'completed_points' => (int) $completedPoints,
                'by_status' => $backlogByStatusCounts,
            ],
            'daily_checkins' => [
                'total_submitted' => $totalCheckins,
                'average_confidence' => round((float) $averageConfidence, 2),
            ],
            'impediments' => [
                'total' => $totalImpediments,
                'resolved' => $resolvedImpediments,
                'by_status' => $impedimentsByStatusCounts,
            ],
            'retrospectives' => [
                'total' => $totalRetrospectives,
                'average_happiness_score' => round((float) $averageHappiness, 2),
            ],
            'peer_reviews' => [
                'total_cycles' => $totalCycles,
                'average_scores' => [
                    'collaboration' => round((float) ($averageScores['collaboration'] ?? 0.0), 2),
                    'delivery' => round((float) ($averageScores['delivery'] ?? 0.0), 2),
                    'communication' => round((float) ($averageScores['communication'] ?? 0.0), 2),
                ],
            ],
        ]);
    }
}
