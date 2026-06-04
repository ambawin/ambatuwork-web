<?php

namespace Tests\Feature;

use App\Models\BacklogItem;
use App\Models\DailyCheckin;
use App\Models\Impediment;
use App\Models\PeerReview;
use App\Models\PeerReviewCycle;
use App\Models\Project;
use App\Models\RetroItem;
use App\Models\Retrospective;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase3ApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $member;
    private User $supervisor;
    private Project $project;
    private Sprint $sprint;
    private BacklogItem $itemOne;
    private BacklogItem $itemTwo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Owner']);
        $this->member = User::factory()->create(['name' => 'Member']);
        $this->supervisor = User::factory()->create(['name' => 'Supervisor']);

        $this->project = Project::create([
            'owner_user_id' => $this->owner->id,
            'name' => 'Phase 3 Test Project',
            'product_goal' => 'Verify Phase 3 features.',
            'default_sprint_length_days' => 14,
            'status' => 'active',
        ]);

        $this->project->memberships()->create([
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->project->memberships()->create([
            'user_id' => $this->member->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->project->memberships()->create([
            'user_id' => $this->supervisor->id,
            'role' => 'supervisor',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->itemOne = $this->project->backlogItems()->create([
            'title' => 'Backlog Item 1',
            'type' => 'story',
            'status' => 'backlog',
            'created_by_user_id' => $this->owner->id,
        ]);

        $this->itemTwo = $this->project->backlogItems()->create([
            'title' => 'Backlog Item 2',
            'type' => 'story',
            'status' => 'backlog',
            'created_by_user_id' => $this->owner->id,
        ]);

        $this->sprint = $this->project->sprints()->create([
            'name' => 'Sprint 1',
            'sprint_goal' => 'Launch Phase 3',
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'created_by_user_id' => $this->owner->id,
        ]);

        $this->sprint->items()->attach([
            $this->itemOne->id => ['added_by_user_id' => $this->owner->id],
            $this->itemTwo->id => ['added_by_user_id' => $this->owner->id],
        ]);
    }

    // --- DAILY CHECK-INS TEST ---

    public function test_members_and_owners_can_checkin_and_trigger_blockers(): void
    {
        Sanctum::actingAs($this->member);

        $response = $this->postJson("/api/v1/projects/{$this->project->id}/sprints/{$this->sprint->id}/checkins", [
            'yesterday' => 'Implemented tests',
            'today' => 'Debugging routes',
            'blockers' => 'Database connection timeout',
            'confidence_score' => 4,
            'checkin_date' => now()->toDateString(),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.confidence_score', 4);

        $this->assertDatabaseHas('daily_checkins', [
            'project_id' => $this->project->id,
            'sprint_id' => $this->sprint->id,
            'user_id' => $this->member->id,
            'confidence_score' => 4,
        ]);

        // Blocker automation check
        $this->assertDatabaseHas('impediments', [
            'project_id' => $this->project->id,
            'sprint_id' => $this->sprint->id,
            'reported_by_user_id' => $this->member->id,
            'description' => 'Database connection timeout',
            'status' => 'open',
        ]);
    }

    public function test_supervisor_cannot_checkin(): void
    {
        Sanctum::actingAs($this->supervisor);

        $response = $this->postJson("/api/v1/projects/{$this->project->id}/sprints/{$this->sprint->id}/checkins", [
            'confidence_score' => 4,
        ]);

        $response->assertForbidden();
    }

    public function test_cannot_submit_duplicate_checkin_on_same_day(): void
    {
        Sanctum::actingAs($this->member);

        DailyCheckin::create([
            'project_id' => $this->project->id,
            'sprint_id' => $this->sprint->id,
            'user_id' => $this->member->id,
            'checkin_date' => now()->toDateString(),
            'confidence_score' => 3,
        ]);

        $response = $this->postJson("/api/v1/projects/{$this->project->id}/sprints/{$this->sprint->id}/checkins", [
            'confidence_score' => 4,
            'checkin_date' => now()->toDateString(),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['checkin_date']);
    }

    // --- IMPEDIMENTS TEST ---

    public function test_member_can_manage_impediments(): void
    {
        Sanctum::actingAs($this->member);

        $response = $this->postJson("/api/v1/projects/{$this->project->id}/impediments", [
            'title' => 'Slow servers',
            'description' => 'Docker is too slow.',
        ]);

        $response->assertCreated();
        $id = $response->json('data.id');

        $this->assertDatabaseHas('impediments', [
            'id' => $id,
            'title' => 'Slow servers',
            'status' => 'open',
        ]);

        // Resolve blocker
        $resolveResponse = $this->postJson("/api/v1/projects/{$this->project->id}/impediments/{$id}/resolve");
        $resolveResponse->assertOk();

        $this->assertDatabaseHas('impediments', [
            'id' => $id,
            'status' => 'resolved',
        ]);
        $this->assertNotNull($resolveResponse->json('data.resolved_at'));
    }

    public function test_supervisor_cannot_mutate_impediments(): void
    {
        $impediment = $this->project->impediments()->create([
            'title' => 'Stuck',
            'reported_by_user_id' => $this->member->id,
        ]);

        Sanctum::actingAs($this->supervisor);

        $this->postJson("/api/v1/projects/{$this->project->id}/impediments", [
            'title' => 'Try',
        ])->assertForbidden();

        $this->postJson("/api/v1/projects/{$this->project->id}/impediments/{$impediment->id}/resolve")
            ->assertForbidden();
    }

    // --- SPRINT REVIEW TEST ---

    public function test_owner_can_submit_sprint_review_and_update_backlog(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson("/api/v1/projects/{$this->project->id}/sprints/{$this->sprint->id}/review", [
            'summary' => 'Finished core features.',
            'demo_url' => 'https://example.com/demo',
            'items' => [
                [
                    'backlog_item_id' => $this->itemOne->id,
                    'decision' => 'accepted',
                    'notes' => 'Looking good',
                ],
                [
                    'backlog_item_id' => $this->itemTwo->id,
                    'decision' => 'carry_over',
                    'notes' => 'Requires more refactoring',
                ]
            ],
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('sprint_reviews', [
            'sprint_id' => $this->sprint->id,
            'summary' => 'Finished core features.',
        ]);

        $this->assertDatabaseHas('sprint_review_items', [
            'backlog_item_id' => $this->itemOne->id,
            'decision' => 'accepted',
        ]);

        // Status update check
        $this->assertEquals('done', $this->itemOne->refresh()->status);
        $this->assertNotNull($this->itemOne->done_at);

        $this->assertEquals('ready', $this->itemTwo->refresh()->status);
        $this->assertNull($this->itemTwo->done_at);
    }

    public function test_member_cannot_submit_review(): void
    {
        Sanctum::actingAs($this->member);

        $response = $this->postJson("/api/v1/projects/{$this->project->id}/sprints/{$this->sprint->id}/review", [
            'items' => [],
        ]);

        $response->assertForbidden();
    }

    // --- RETROSPECTIVE TEST ---

    public function test_member_and_owner_can_manage_retro_items(): void
    {
        Sanctum::actingAs($this->member);

        // Store overall Retro details (Happiness Score)
        $this->postJson("/api/v1/projects/{$this->project->id}/sprints/{$this->sprint->id}/retrospective", [
            'team_happiness_score' => 5,
        ])->assertSuccessful();

        $this->assertDatabaseHas('retrospectives', [
            'sprint_id' => $this->sprint->id,
            'team_happiness_score' => 5,
        ]);

        // Submit retro item
        $response = $this->postJson("/api/v1/projects/{$this->project->id}/sprints/{$this->sprint->id}/retrospective/items", [
            'type' => 'went_well',
            'body' => 'Collaboration was awesome!',
        ]);

        $response->assertCreated();
        $itemId = $response->json('data.id');

        $this->assertDatabaseHas('retro_items', [
            'id' => $itemId,
            'type' => 'went_well',
            'author_user_id' => $this->member->id,
        ]);

        // Modify own item
        $this->patchJson("/api/v1/projects/{$this->project->id}/sprints/{$this->sprint->id}/retrospective/items/{$itemId}", [
            'type' => 'went_well',
            'body' => 'Collaboration was extremely awesome!',
        ])->assertOk();

        $this->assertDatabaseHas('retro_items', [
            'id' => $itemId,
            'body' => 'Collaboration was extremely awesome!',
        ]);

        // Supervisor try to delete
        Sanctum::actingAs($this->supervisor);
        $this->deleteJson("/api/v1/projects/{$this->project->id}/sprints/{$this->sprint->id}/retrospective/items/{$itemId}")
            ->assertForbidden();

        // Delete own item
        Sanctum::actingAs($this->member);
        $this->deleteJson("/api/v1/projects/{$this->project->id}/sprints/{$this->sprint->id}/retrospective/items/{$itemId}")
            ->assertOk();

        $this->assertDatabaseMissing('retro_items', ['id' => $itemId]);
    }

    // --- PEER REVIEW TEST ---

    public function test_peer_review_lifecycle_and_rules(): void
    {
        // 1. Owner starts the cycle
        Sanctum::actingAs($this->owner);
        $startResponse = $this->postJson("/api/v1/projects/{$this->project->id}/sprints/{$this->sprint->id}/peer-review-cycle");
        $startResponse->assertCreated();
        $cycleId = $startResponse->json('data.id');

        $this->assertDatabaseHas('peer_review_cycles', [
            'id' => $cycleId,
            'status' => 'open',
        ]);

        // 2. Member submits peer review for owner
        Sanctum::actingAs($this->member);
        $reviewResponse = $this->postJson("/api/v1/projects/{$this->project->id}/peer-review-cycles/{$cycleId}/reviews", [
            'reviewee_user_id' => $this->owner->id,
            'collaboration_score' => 5,
            'delivery_score' => 4,
            'communication_score' => 5,
            'continue_feedback' => 'Good planning.',
            'improve_feedback' => 'None.',
        ]);

        $reviewResponse->assertCreated();
        $this->assertDatabaseHas('peer_reviews', [
            'peer_review_cycle_id' => $cycleId,
            'reviewer_user_id' => $this->member->id,
            'reviewee_user_id' => $this->owner->id,
            'collaboration_score' => 5,
        ]);

        // Self review check
        $this->postJson("/api/v1/projects/{$this->project->id}/peer-review-cycles/{$cycleId}/reviews", [
            'reviewee_user_id' => $this->member->id,
            'collaboration_score' => 5,
            'delivery_score' => 4,
            'communication_score' => 5,
        ])->assertUnprocessable();

        // Supervisor review check (cannot review supervisor)
        $this->postJson("/api/v1/projects/{$this->project->id}/peer-review-cycles/{$cycleId}/reviews", [
            'reviewee_user_id' => $this->supervisor->id,
            'collaboration_score' => 5,
            'delivery_score' => 4,
            'communication_score' => 5,
        ])->assertUnprocessable();

        // 3. Member queries my-summary (should be aggregated & anonymous)
        $summaryResponse = $this->getJson("/api/v1/projects/{$this->project->id}/peer-review-cycles/{$cycleId}/my-summary");
        $summaryResponse->assertOk();
        $summaryResponse->assertJsonPath('data.review_count', 0); // No one reviewed member yet

        // 4. Owner queries detailed summary
        Sanctum::actingAs($this->owner);
        $ownerSummaryResponse = $this->getJson("/api/v1/projects/{$this->project->id}/peer-review-cycles/{$cycleId}/summary");
        $ownerSummaryResponse->assertOk();
        
        // Find owner details in summary array
        $ownerData = collect($ownerSummaryResponse->json('data'))->firstWhere('user.id', $this->owner->id);
        $this->assertEquals(1, $ownerData['review_count']);
        $this->assertEquals(5, $ownerData['avg_collaboration_score']);

        // 5. Close cycle
        $this->postJson("/api/v1/projects/{$this->project->id}/peer-review-cycles/{$cycleId}/close")->assertOk();
        $this->assertDatabaseHas('peer_review_cycles', [
            'id' => $cycleId,
            'status' => 'closed',
        ]);

        // Post review after close yields validation error
        Sanctum::actingAs($this->member);
        $this->postJson("/api/v1/projects/{$this->project->id}/peer-review-cycles/{$cycleId}/reviews", [
            'reviewee_user_id' => $this->owner->id,
            'collaboration_score' => 5,
            'delivery_score' => 4,
            'communication_score' => 5,
        ])->assertUnprocessable();
    }
}
