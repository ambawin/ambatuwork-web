<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BacklogItemApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_create_list_update_and_archive_backlog_item(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = $this->createProjectForUser($owner);

        $project->memberships()->create([
            'user_id' => $member->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($member);

        $createResponse = $this->postJson("/api/v1/projects/{$project->id}/backlog-items", [
            'title' => 'Invite users by email',
            'description' => 'Allow project owners to invite collaborators.',
            'type' => 'story',
            'priority' => 'high',
            'estimate_points' => 5,
            'acceptance_criteria' => [
                'Owner can invite a user by email',
                'Invitee receives an invitation link',
            ],
            'assigned_to_user_id' => $member->id,
        ]);

        $createResponse->assertCreated();
        $createResponse->assertJsonPath('data.title', 'Invite users by email');
        $createResponse->assertJsonPath('data.status', 'backlog');
        $createResponse->assertJsonPath('data.assigned_to_user_id', $member->id);

        $backlogItemId = $createResponse->json('data.id');

        $indexResponse = $this->getJson("/api/v1/projects/{$project->id}/backlog-items");

        $indexResponse->assertOk();
        $indexResponse->assertJsonCount(1, 'data');
        $indexResponse->assertJsonPath('data.0.id', $backlogItemId);

        $updateResponse = $this->patchJson("/api/v1/projects/{$project->id}/backlog-items/{$backlogItemId}", [
            'title' => 'Invite users by email and role',
            'status' => 'ready',
        ]);

        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('data.title', 'Invite users by email and role');
        $updateResponse->assertJsonPath('data.status', 'ready');

        $deleteResponse = $this->deleteJson("/api/v1/projects/{$project->id}/backlog-items/{$backlogItemId}");

        $deleteResponse->assertOk();
        $deleteResponse->assertJsonPath('message', 'Backlog item archived.');
        $deleteResponse->assertJsonPath('data.status', 'archived');

        $this->assertDatabaseHas('backlog_items', [
            'id' => $backlogItemId,
            'project_id' => $project->id,
            'status' => 'archived',
        ]);
    }

    public function test_supervisor_cannot_create_backlog_item(): void
    {
        $owner = User::factory()->create();
        $supervisor = User::factory()->create();
        $project = $this->createProjectForUser($owner);

        $project->memberships()->create([
            'user_id' => $supervisor->id,
            'role' => 'supervisor',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($supervisor);

        $response = $this->postJson("/api/v1/projects/{$project->id}/backlog-items", [
            'title' => 'Blocked action',
        ]);

        $response->assertForbidden();
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

        $project->definitionsOfDone()->create([
            'title' => 'Default Definition of Done',
            'checklist' => [
                'Acceptance criteria are completed',
                'Reviewed by another team member',
                'No known critical bugs',
                'Demoable in Sprint Review',
            ],
            'is_active' => true,
            'created_by_user_id' => $user->id,
        ]);

        return $project;
    }
}