<?php

namespace Tests\Feature;

use App\Models\BacklogItem;
use App\Models\DailyCheckin;
use App\Models\Impediment;
use App\Models\PeerReview;
use App\Models\PeerReviewCycle;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_stats(): void
    {
        $response = $this->getJson('/api/v1/users/me/stats');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_with_no_activity_returns_zero_stats(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/users/me/stats');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'projects' => [
                    'total_active' => 0,
                ],
                'backlog_items' => [
                    'assigned_total' => 0,
                    'assigned_by_status' => [
                        'backlog' => 0,
                        'ready' => 0,
                        'selected' => 0,
                        'in_progress' => 0,
                        'in_review' => 0,
                        'done' => 0,
                    ],
                    'completed_points' => 0,
                ],
                'daily_checkins' => [
                    'total_submitted' => 0,
                    'average_confidence' => 0.0,
                ],
                'impediments' => [
                    'reported_total' => 0,
                    'reported_resolved' => 0,
                    'reported_by_status' => [
                        'open' => 0,
                        'in_progress' => 0,
                        'resolved' => 0,
                        'ignored' => 0,
                    ],
                ],
                'peer_reviews' => [
                    'submitted_total' => 0,
                    'received_total' => 0,
                    'received_average_scores' => [
                        'collaboration' => 0.0,
                        'delivery' => 0.0,
                        'communication' => 0.0,
                    ],
                ],
            ],
        ]);
    }

    public function test_authenticated_user_returns_correct_calculated_stats(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // 1. Projects (2 active, 1 inactive/pending)
        $project1 = Project::create([
            'owner_user_id' => $user->id,
            'name' => 'Project 1',
            'product_goal' => 'Goal 1',
            'status' => 'active',
        ]);
        $project1->memberships()->create(['user_id' => $user->id, 'role' => 'owner', 'status' => 'active', 'joined_at' => now()]);

        $project2 = Project::create([
            'owner_user_id' => $otherUser->id,
            'name' => 'Project 2',
            'product_goal' => 'Goal 2',
            'status' => 'active',
        ]);
        $project2->memberships()->create(['user_id' => $user->id, 'role' => 'member', 'status' => 'active', 'joined_at' => now()]);

        $project3 = Project::create([
            'owner_user_id' => $otherUser->id,
            'name' => 'Project 3',
            'product_goal' => 'Goal 3',
            'status' => 'active',
        ]);
        $project3->memberships()->create(['user_id' => $user->id, 'role' => 'member', 'status' => 'pending', 'joined_at' => now()]);

        // 2. Backlog Items
        // 2.1 Backlog status (estimate: 3)
        BacklogItem::create([
            'project_id' => $project1->id,
            'title' => 'Backlog Task',
            'status' => 'backlog',
            'estimate_points' => 3,
            'created_by_user_id' => $user->id,
            'assigned_to_user_id' => $user->id,
        ]);
        // 2.2 In Progress status (estimate: 5)
        BacklogItem::create([
            'project_id' => $project1->id,
            'title' => 'In Progress Task',
            'status' => 'in_progress',
            'estimate_points' => 5,
            'created_by_user_id' => $user->id,
            'assigned_to_user_id' => $user->id,
        ]);
        // 2.3 Done status (estimate: 8)
        BacklogItem::create([
            'project_id' => $project1->id,
            'title' => 'Done Task 1',
            'status' => 'done',
            'estimate_points' => 8,
            'created_by_user_id' => $user->id,
            'assigned_to_user_id' => $user->id,
            'done_at' => now(),
        ]);
        // 2.4 Done status (estimate: 2)
        BacklogItem::create([
            'project_id' => $project2->id,
            'title' => 'Done Task 2',
            'status' => 'done',
            'estimate_points' => 2,
            'created_by_user_id' => $user->id,
            'assigned_to_user_id' => $user->id,
            'done_at' => now(),
        ]);
        // 2.5 Archived status (should not count)
        BacklogItem::create([
            'project_id' => $project1->id,
            'title' => 'Archived Task',
            'status' => 'archived',
            'estimate_points' => 10,
            'created_by_user_id' => $user->id,
            'assigned_to_user_id' => $user->id,
        ]);
        // 2.6 Assigned to someone else (should not count)
        BacklogItem::create([
            'project_id' => $project1->id,
            'title' => 'Other Task',
            'status' => 'done',
            'estimate_points' => 10,
            'created_by_user_id' => $user->id,
            'assigned_to_user_id' => $otherUser->id,
        ]);

        // 3. Daily Checkins (total 2, confidence scores 4 and 5)
        $sprint = Sprint::create([
            'project_id' => $project1->id,
            'name' => 'Sprint 1',
            'sprint_goal' => 'Launch Phase 3',
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'created_by_user_id' => $user->id,
        ]);
        DailyCheckin::create([
            'project_id' => $project1->id,
            'sprint_id' => $sprint->id,
            'user_id' => $user->id,
            'checkin_date' => now()->subDay()->toDateString(),
            'confidence_score' => 4,
        ]);
        DailyCheckin::create([
            'project_id' => $project1->id,
            'sprint_id' => $sprint->id,
            'user_id' => $user->id,
            'checkin_date' => now()->toDateString(),
            'confidence_score' => 5,
        ]);

        // 4. Impediments (reported: 1 resolved, 1 open, 1 ignored)
        Impediment::create([
            'project_id' => $project1->id,
            'reported_by_user_id' => $user->id,
            'title' => 'Blocker 1',
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
        Impediment::create([
            'project_id' => $project1->id,
            'reported_by_user_id' => $user->id,
            'title' => 'Blocker 2',
            'status' => 'open',
        ]);
        Impediment::create([
            'project_id' => $project1->id,
            'reported_by_user_id' => $user->id,
            'title' => 'Blocker 3',
            'status' => 'ignored',
        ]);
        // Impediment reported by other (should not count)
        Impediment::create([
            'project_id' => $project1->id,
            'reported_by_user_id' => $otherUser->id,
            'title' => 'Blocker 4',
            'status' => 'open',
        ]);

        // 5. Peer Reviews
        $cycle = PeerReviewCycle::create([
            'project_id' => $project1->id,
            'sprint_id' => $sprint->id,
            'status' => 'open',
            'created_by_user_id' => $user->id,
        ]);

        // Submitted by user
        PeerReview::create([
            'peer_review_cycle_id' => $cycle->id,
            'reviewer_user_id' => $user->id,
            'reviewee_user_id' => $otherUser->id,
            'collaboration_score' => 5,
            'delivery_score' => 5,
            'communication_score' => 5,
        ]);

        // Received by user review 1 (collaboration 4, delivery 3, communication 5)
        PeerReview::create([
            'peer_review_cycle_id' => $cycle->id,
            'reviewer_user_id' => $otherUser->id,
            'reviewee_user_id' => $user->id,
            'collaboration_score' => 4,
            'delivery_score' => 3,
            'communication_score' => 5,
        ]);

        // Received by user review 2 (collaboration 5, delivery 4, communication 4)
        $anotherUser = User::factory()->create();
        PeerReview::create([
            'peer_review_cycle_id' => $cycle->id,
            'reviewer_user_id' => $anotherUser->id,
            'reviewee_user_id' => $user->id,
            'collaboration_score' => 5,
            'delivery_score' => 4,
            'communication_score' => 4,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/users/me/stats');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'projects' => [
                    'total_active' => 2,
                ],
                'backlog_items' => [
                    'assigned_total' => 4,
                    'assigned_by_status' => [
                        'backlog' => 1,
                        'ready' => 0,
                        'selected' => 0,
                        'in_progress' => 1,
                        'in_review' => 0,
                        'done' => 2,
                    ],
                    'completed_points' => 10, // 8 + 2
                ],
                'daily_checkins' => [
                    'total_submitted' => 2,
                    'average_confidence' => 4.5,
                ],
                'impediments' => [
                    'reported_total' => 3,
                    'reported_resolved' => 1,
                    'reported_by_status' => [
                        'open' => 1,
                        'in_progress' => 0,
                        'resolved' => 1,
                        'ignored' => 1,
                    ],
                ],
                'peer_reviews' => [
                    'submitted_total' => 1,
                    'received_total' => 2,
                    'received_average_scores' => [
                        'collaboration' => 4.5,
                        'delivery' => 3.5,
                        'communication' => 4.5,
                    ],
                ],
            ],
        ]);
    }
}
