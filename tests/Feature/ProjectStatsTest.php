<?php

namespace Tests\Feature;

use App\Models\BacklogItem;
use App\Models\DailyCheckin;
use App\Models\Impediment;
use App\Models\PeerReview;
use App\Models\PeerReviewCycle;
use App\Models\Project;
use App\Models\Retrospective;
use App\Models\Sprint;
use App\Models\SprintItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_project_stats(): void
    {
        $owner = User::factory()->create();
        $project = Project::create([
            'owner_user_id' => $owner->id,
            'name' => 'Project Alpha',
            'product_goal' => 'Goal Alpha',
            'status' => 'active',
        ]);
        $response = $this->getJson("/api/v1/projects/{$project->id}/stats");
        $response->assertStatus(401);
    }

    public function test_non_member_cannot_access_project_stats(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $project = Project::create([
            'owner_user_id' => $owner->id,
            'name' => 'Project Alpha',
            'product_goal' => 'Goal Alpha',
            'status' => 'active',
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/projects/{$project->id}/stats");
        $response->assertStatus(403);
    }

    public function test_project_member_gets_correct_calculated_stats(): void
    {
        $user = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();
        $otherUser = User::factory()->create();

        // 1. Project creation
        $project = Project::create([
            'owner_user_id' => $user->id,
            'name' => 'Project Alpha',
            'product_goal' => 'Goal Alpha',
            'status' => 'active',
        ]);

        // Memberships: 1 owner, 1 supervisor, 1 member, plus a pending one (which shouldn't count)
        $project->memberships()->create(['user_id' => $user->id, 'role' => 'owner', 'status' => 'active', 'joined_at' => now()]);
        $project->memberships()->create(['user_id' => $member1->id, 'role' => 'supervisor', 'status' => 'active', 'joined_at' => now()]);
        $project->memberships()->create(['user_id' => $member2->id, 'role' => 'member', 'status' => 'active', 'joined_at' => now()]);
        $project->memberships()->create(['user_id' => $otherUser->id, 'role' => 'member', 'status' => 'pending', 'joined_at' => now()]);

        // 2. Sprints & Velocity
        // Sprint 1 (closed)
        $sprint1 = Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 1',
            'sprint_goal' => 'Goal 1',
            'status' => 'closed',
            'start_date' => now()->subDays(20)->toDateString(),
            'end_date' => now()->subDays(7)->toDateString(),
            'created_by_user_id' => $user->id,
            'closed_by_user_id' => $user->id,
            'closed_at' => now()->subDays(7),
        ]);

        // Sprint 2 (active)
        $sprint2 = Sprint::create([
            'project_id' => $project->id,
            'name' => 'Sprint 2',
            'sprint_goal' => 'Goal 2',
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'created_by_user_id' => $user->id,
        ]);

        // Backlog items inside/outside Sprints
        // Item 1 (Sprint 1, Done - 5 points) -> counts towards velocity
        $item1 = BacklogItem::create([
            'project_id' => $project->id,
            'title' => 'Task 1',
            'status' => 'done',
            'estimate_points' => 5,
            'created_by_user_id' => $user->id,
        ]);
        SprintItem::create([
            'sprint_id' => $sprint1->id,
            'backlog_item_id' => $item1->id,
            'committed_points' => 5,
            'added_by_user_id' => $user->id,
            'added_at' => now()->subDays(19),
        ]);

        // Item 2 (Sprint 1, In Progress - 8 points) -> NOT done in closed sprint, so velocity doesn't get this
        $item2 = BacklogItem::create([
            'project_id' => $project->id,
            'title' => 'Task 2',
            'status' => 'in_progress',
            'estimate_points' => 8,
            'created_by_user_id' => $user->id,
        ]);
        SprintItem::create([
            'sprint_id' => $sprint1->id,
            'backlog_item_id' => $item2->id,
            'committed_points' => 8,
            'added_by_user_id' => $user->id,
            'added_at' => now()->subDays(19),
        ]);

        // Item 3 (Sprint 2, Done - 3 points) -> active sprint, so doesn't count towards closed sprint velocity
        $item3 = BacklogItem::create([
            'project_id' => $project->id,
            'title' => 'Task 3',
            'status' => 'done',
            'estimate_points' => 3,
            'created_by_user_id' => $user->id,
        ]);
        SprintItem::create([
            'sprint_id' => $sprint2->id,
            'backlog_item_id' => $item3->id,
            'committed_points' => 3,
            'added_by_user_id' => $user->id,
            'added_at' => now(),
        ]);

        // Item 4 (No sprint, Backlog status, Archived - should not count anywhere)
        BacklogItem::create([
            'project_id' => $project->id,
            'title' => 'Archived Task',
            'status' => 'archived',
            'estimate_points' => 10,
            'created_by_user_id' => $user->id,
        ]);

        // Item 5 (No sprint, Backlog status - 2 points)
        BacklogItem::create([
            'project_id' => $project->id,
            'title' => 'Backlog Task',
            'status' => 'backlog',
            'estimate_points' => 2,
            'created_by_user_id' => $user->id,
        ]);

        // Total non-archived items: Item 1 (done, 5pt), Item 2 (in_progress, 8pt), Item 3 (done, 3pt), Item 5 (backlog, 2pt)
        // Total items = 4
        // Total points = 18
        // Completed points = 8 (Item 1 & Item 3)

        // 3. Daily check-ins
        DailyCheckin::create([
            'project_id' => $project->id,
            'sprint_id' => $sprint2->id,
            'user_id' => $user->id,
            'checkin_date' => now()->toDateString(),
            'confidence_score' => 4,
        ]);
        DailyCheckin::create([
            'project_id' => $project->id,
            'sprint_id' => $sprint2->id,
            'user_id' => $member1->id,
            'checkin_date' => now()->toDateString(),
            'confidence_score' => 5,
        ]);

        // 4. Impediments (reported: 1 open, 1 resolved, 1 ignored)
        Impediment::create([
            'project_id' => $project->id,
            'reported_by_user_id' => $member1->id,
            'title' => 'Blocker 1',
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
        Impediment::create([
            'project_id' => $project->id,
            'reported_by_user_id' => $member2->id,
            'title' => 'Blocker 2',
            'status' => 'open',
        ]);
        Impediment::create([
            'project_id' => $project->id,
            'reported_by_user_id' => $member2->id,
            'title' => 'Blocker 3',
            'status' => 'ignored',
        ]);

        // 5. Retrospective
        Retrospective::create([
            'project_id' => $project->id,
            'sprint_id' => $sprint1->id,
            'team_happiness_score' => 4,
        ]);

        // 6. Peer Reviews
        $cycle = PeerReviewCycle::create([
            'project_id' => $project->id,
            'sprint_id' => $sprint1->id,
            'status' => 'closed',
            'created_by_user_id' => $user->id,
        ]);

        PeerReview::create([
            'peer_review_cycle_id' => $cycle->id,
            'reviewer_user_id' => $member1->id,
            'reviewee_user_id' => $member2->id,
            'collaboration_score' => 4,
            'delivery_score' => 5,
            'communication_score' => 3,
        ]);
        PeerReview::create([
            'peer_review_cycle_id' => $cycle->id,
            'reviewer_user_id' => $member2->id,
            'reviewee_user_id' => $member1->id,
            'collaboration_score' => 5,
            'delivery_score' => 3,
            'communication_score' => 5,
        ]);

        Sanctum::actingAs($member1);
        $response = $this->getJson("/api/v1/projects/{$project->id}/stats");

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'project' => [
                    'id' => $project->id,
                    'name' => 'Project Alpha',
                    'status' => 'active',
                ],
                'members' => [
                    'total' => 3,
                    'by_role' => [
                        'owner' => 1,
                        'member' => 1,
                        'supervisor' => 1,
                    ],
                ],
                'sprints' => [
                    'total' => 2,
                    'active' => 1,
                    'completed' => 1,
                    'average_velocity' => 5.0, // Only Item 1 is done in closed sprint
                ],
                'backlog_items' => [
                    'total' => 4,
                    'total_points' => 18,
                    'completed_points' => 8,
                    'by_status' => [
                        'backlog' => 1,
                        'ready' => 0,
                        'selected' => 0,
                        'in_progress' => 1,
                        'in_review' => 0,
                        'done' => 2,
                    ],
                ],
                'daily_checkins' => [
                    'total_submitted' => 2,
                    'average_confidence' => 4.5,
                ],
                'impediments' => [
                    'total' => 3,
                    'resolved' => 1,
                    'by_status' => [
                        'open' => 1,
                        'in_progress' => 0,
                        'resolved' => 1,
                        'ignored' => 1,
                    ],
                ],
                'retrospectives' => [
                    'total' => 1,
                    'average_happiness_score' => 4.0,
                ],
                'peer_reviews' => [
                    'total_cycles' => 1,
                    'average_scores' => [
                        'collaboration' => 4.5, // (4+5)/2
                        'delivery' => 4.0,      // (5+3)/2
                        'communication' => 4.0,  // (3+5)/2
                    ],
                ],
            ],
        ]);
    }
}
