<?php

namespace Tests\Feature;

use App\Jobs\SendFcmNotificationJob;
use App\Models\BacklogItem;
use App\Models\Impediment;
use App\Models\PeerReviewCycle;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PushNotificationTriggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitation_accept_sends_push_notification(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $invitee = User::factory()->create(['email' => 'member@example.com']);
        $project = $this->createProjectForUser($owner);

        $plainToken = 'invite-token-1234567890';

        ProjectInvitation::create([
            'project_id' => $project->id,
            'email' => 'member@example.com',
            'role' => 'member',
            'token_hash' => hash('sha256', $plainToken),
            'status' => 'pending',
            'invited_by_user_id' => $owner->id,
            'expires_at' => now()->addDay(),
        ]);

        Sanctum::actingAs($invitee);

        $response = $this->postJson("/api/v1/invitations/{$plainToken}/accept");
        $response->assertOk();

        Queue::assertPushed(SendFcmNotificationJob::class, function ($job) use ($owner, $project, $invitee) {
            return $job->userId === $owner->id
                && str_contains($job->title, $invitee->name)
                && $job->data['type'] === 'project_invitation_accepted';
        });
    }

    public function test_backlog_item_assigned_sends_push_notification_on_create_and_update(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectForUser($owner);

        $project->memberships()->create([
            'user_id' => $member->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($owner);

        // 1. Assign to member on creation
        $response = $this->postJson("/api/v1/projects/{$project->id}/backlog-items", [
            'title' => 'Task 1',
            'assigned_to_user_id' => $member->id,
        ]);
        $response->assertCreated();

        Queue::assertPushed(SendFcmNotificationJob::class, function ($job) use ($member, $project) {
            return $job->userId === $member->id
                && $job->data['type'] === 'backlog_item_assigned'
                && str_contains($job->title, $project->name);
        });

        // 2. Assign to owner on update (who is doing the update, so no notification should be sent for themselves)
        Queue::fake();
        $itemId = $response->json('data.id');
        $updateResponse = $this->patchJson("/api/v1/projects/{$project->id}/backlog-items/{$itemId}", [
            'assigned_to_user_id' => $owner->id,
        ]);
        $updateResponse->assertOk();
        Queue::assertNotPushed(SendFcmNotificationJob::class);

        // 3. Assign to member again on update (should notify member)
        Queue::fake();
        $updateResponse2 = $this->patchJson("/api/v1/projects/{$project->id}/backlog-items/{$itemId}", [
            'assigned_to_user_id' => $member->id,
        ]);
        $updateResponse2->assertOk();
        Queue::assertPushed(SendFcmNotificationJob::class, function ($job) use ($member) {
            return $job->userId === $member->id;
        });
    }

    public function test_sprint_started_and_closed_sends_push_notification(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectForUser($owner);

        $project->memberships()->create([
            'user_id' => $member->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $backlogItem = $this->createBacklogItem($project, $owner, 'Task 1', 5);

        Sanctum::actingAs($owner);

        $sprintResponse = $this->postJson("/api/v1/projects/{$project->id}/sprints", [
            'name' => 'Sprint 1',
            'sprint_goal' => 'Goal 1',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-14',
            'backlog_item_ids' => [$backlogItem->id],
        ]);
        $sprintResponse->assertCreated();
        $sprintId = $sprintResponse->json('data.id');

        // Start Sprint
        $startResponse = $this->postJson("/api/v1/projects/{$project->id}/sprints/{$sprintId}/start");
        $startResponse->assertOk();

        Queue::assertPushed(SendFcmNotificationJob::class, function ($job) use ($member, $project) {
            return $job->userId === $member->id
                && $job->data['type'] === 'sprint_started'
                && str_contains($job->title, $project->name);
        });

        // Close Sprint
        Queue::fake();
        $closeResponse = $this->postJson("/api/v1/projects/{$project->id}/sprints/{$sprintId}/close");
        $closeResponse->assertOk();

        Queue::assertPushed(SendFcmNotificationJob::class, function ($job) use ($member, $project) {
            return $job->userId === $member->id
                && $job->data['type'] === 'sprint_closed'
                && str_contains($job->title, $project->name);
        });
    }

    public function test_impediment_reported_and_resolved_sends_push_notification(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $member = User::factory()->create();
        $supervisor = User::factory()->create();
        $project = $this->createProjectForUser($owner);

        $project->memberships()->create([
            'user_id' => $member->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $project->memberships()->create([
            'user_id' => $supervisor->id,
            'role' => 'supervisor',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($member);

        // 1. Report blocker - notifies project owner and supervisor
        $response = $this->postJson("/api/v1/projects/{$project->id}/impediments", [
            'title' => 'Blocker title',
            'description' => 'Blocker description',
        ]);
        $response->assertCreated();
        $impedimentId = $response->json('data.id');

        Queue::assertPushed(SendFcmNotificationJob::class, function ($job) use ($owner) {
            return $job->userId === $owner->id
                && $job->data['type'] === 'impediment_reported';
        });

        Queue::assertPushed(SendFcmNotificationJob::class, function ($job) use ($supervisor) {
            return $job->userId === $supervisor->id
                && $job->data['type'] === 'impediment_reported';
        });

        // 2. Resolve blocker - notifies reporter (member)
        Queue::fake();
        Sanctum::actingAs($owner);
        $resolveResponse = $this->postJson("/api/v1/projects/{$project->id}/impediments/{$impedimentId}/resolve");
        $resolveResponse->assertOk();

        Queue::assertPushed(SendFcmNotificationJob::class, function ($job) use ($member) {
            return $job->userId === $member->id
                && $job->data['type'] === 'impediment_resolved';
        });
    }

    public function test_peer_review_cycle_opened_and_closed_sends_push_notification(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $member = User::factory()->create();
        $supervisor = User::factory()->create();
        $project = $this->createProjectForUser($owner);

        $project->memberships()->create([
            'user_id' => $member->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $project->memberships()->create([
            'user_id' => $supervisor->id,
            'role' => 'supervisor',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $backlogItem = $this->createBacklogItem($project, $owner, 'Task 1', 5);
        Sanctum::actingAs($owner);

        $sprintResponse = $this->postJson("/api/v1/projects/{$project->id}/sprints", [
            'name' => 'Sprint 1',
            'sprint_goal' => 'Goal 1',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-14',
            'backlog_item_ids' => [$backlogItem->id],
        ]);
        $sprintId = $sprintResponse->json('data.id');

        // 1. Open peer review cycle
        $cycleResponse = $this->postJson("/api/v1/projects/{$project->id}/sprints/{$sprintId}/peer-review-cycle");
        $cycleResponse->assertCreated();
        $cycleId = $cycleResponse->json('data.id');

        // Notifies member but NOT supervisor
        Queue::assertPushed(SendFcmNotificationJob::class, function ($job) use ($member) {
            return $job->userId === $member->id
                && $job->data['type'] === 'peer_review_cycle_opened';
        });

        Queue::assertNotPushed(SendFcmNotificationJob::class, function ($job) use ($supervisor) {
            return $job->userId === $supervisor->id;
        });

        // 2. Close peer review cycle
        Queue::fake();
        $closeResponse = $this->postJson("/api/v1/projects/{$project->id}/peer-review-cycles/{$cycleId}/close");
        $closeResponse->assertOk();

        // Notifies member but NOT supervisor
        Queue::assertPushed(SendFcmNotificationJob::class, function ($job) use ($member) {
            return $job->userId === $member->id
                && $job->data['type'] === 'peer_review_cycle_closed';
        });

        Queue::assertNotPushed(SendFcmNotificationJob::class, function ($job) use ($supervisor) {
            return $job->userId === $supervisor->id;
        });
    }

    private function createProjectForUser(User $user): Project
    {
        $project = Project::create([
            'owner_user_id' => $user->id,
            'name' => 'Phase 2 Project',
            'description' => null,
            'product_goal' => 'Ship phase 2.',
            'default_sprint_length_days' => 14,
            'status' => 'active',
        ]);

        $project->memberships()->create([
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return $project;
    }

    private function createBacklogItem(Project $project, User $user, string $title, int $estimatePoints): BacklogItem
    {
        return $project->backlogItems()->create([
            'title' => $title,
            'description' => null,
            'type' => 'story',
            'status' => 'backlog',
            'priority' => 'medium',
            'estimate_points' => $estimatePoints,
            'acceptance_criteria' => ['Done when accepted'],
            'created_by_user_id' => $user->id,
            'assigned_to_user_id' => null,
        ]);
    }
}
