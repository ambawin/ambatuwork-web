<?php

namespace Tests\Feature;

use App\Models\BacklogItem;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SprintApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_start_view_board_and_close_sprint(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectForUser($owner);
        $backlogItemOne = $this->createBacklogItem($project, $owner, 'Create projects', 5);
        $backlogItemTwo = $this->createBacklogItem($project, $owner, 'Invite users', 3);

        Sanctum::actingAs($owner);

        $createResponse = $this->postJson("/api/v1/projects/{$project->id}/sprints", [
            'name' => 'Sprint 1',
            'sprint_goal' => 'Launch project creation and invitations.',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-14',
            'backlog_item_ids' => [$backlogItemOne->id, $backlogItemTwo->id],
        ]);

        $createResponse->assertCreated();
        $createResponse->assertJsonPath('data.name', 'Sprint 1');
        $createResponse->assertJsonPath('data.status', 'planned');
        $createResponse->assertJsonPath('data.item_count', 2);

        $sprintId = $createResponse->json('data.id');

        $this->assertDatabaseHas('sprints', [
            'id' => $sprintId,
            'project_id' => $project->id,
            'status' => 'planned',
        ]);

        $this->assertDatabaseHas('backlog_items', [
            'id' => $backlogItemOne->id,
            'status' => 'selected',
        ]);

        $startResponse = $this->postJson("/api/v1/projects/{$project->id}/sprints/{$sprintId}/start");

        $startResponse->assertOk();
        $startResponse->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('sprints', [
            'id' => $sprintId,
            'status' => 'active',
        ]);

        $boardResponse = $this->getJson("/api/v1/projects/{$project->id}/sprints/{$sprintId}/board");

        $boardResponse->assertOk();
        $boardResponse->assertJsonPath('data.sprint.id', $sprintId);
        $boardResponse->assertJsonCount(2, 'data.columns.selected');

        BacklogItem::query()->whereKey($backlogItemOne->id)->update(['status' => 'done']);

        $closeResponse = $this->postJson("/api/v1/projects/{$project->id}/sprints/{$sprintId}/close");

        $closeResponse->assertOk();
        $closeResponse->assertJsonPath('message', 'Sprint closed.');
        $closeResponse->assertJsonPath('data.status', 'closed');

        $this->assertDatabaseHas('sprints', [
            'id' => $sprintId,
            'status' => 'closed',
        ]);

        $this->assertDatabaseHas('backlog_items', [
            'id' => $backlogItemTwo->id,
            'status' => 'ready',
        ]);
    }

    public function test_owner_cannot_create_second_active_sprint(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectForUser($owner);
        $backlogItem = $this->createBacklogItem($project, $owner, 'Create projects', 5);

        Sanctum::actingAs($owner);

        $firstSprint = $this->postJson("/api/v1/projects/{$project->id}/sprints", [
            'name' => 'Sprint 1',
            'sprint_goal' => 'Ship phase 1.',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-14',
            'backlog_item_ids' => [$backlogItem->id],
        ]);

        $firstSprint->assertCreated();

        $this->postJson("/api/v1/projects/{$project->id}/sprints/{$firstSprint->json('data.id')}/start")
            ->assertOk();

        $secondBacklogItem = $this->createBacklogItem($project, $owner, 'Invite users', 3);

        $response = $this->postJson("/api/v1/projects/{$project->id}/sprints", [
            'name' => 'Sprint 2',
            'sprint_goal' => 'Ship phase 2.',
            'start_date' => '2026-05-15',
            'end_date' => '2026-05-28',
            'backlog_item_ids' => [$secondBacklogItem->id],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['sprint']);
    }

    public function test_supervisor_cannot_create_sprint(): void
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

        $backlogItem = $this->createBacklogItem($project, $owner, 'Create projects', 5);

        Sanctum::actingAs($supervisor);

        $response = $this->postJson("/api/v1/projects/{$project->id}/sprints", [
            'name' => 'Sprint 1',
            'sprint_goal' => 'Ship phase 1.',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-14',
            'backlog_item_ids' => [$backlogItem->id],
        ]);

        $response->assertForbidden();
    }

    public function test_sprint_creation_fails_if_duration_exceeds_project_limit(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectForUser($owner);
        $project->update(['default_sprint_length_days' => 7]);
        $backlogItem = $this->createBacklogItem($project, $owner, 'Create projects', 5);

        Sanctum::actingAs($owner);

        // Try to create an 8-day sprint (exceeding project limit of 7)
        $response = $this->postJson("/api/v1/projects/{$project->id}/sprints", [
            'name' => 'Sprint 1',
            'sprint_goal' => 'Launch project.',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-09',
            'backlog_item_ids' => [$backlogItem->id],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['end_date']);
    }

    private function createProjectForUser(User $user): Project
    {
        $project = Project::create([
            'owner_user_id' => $user->id,
            'name' => 'Phase 2 Project',
            'description' => null,
            'product_goal' => 'Ship phase 2.',
            'default_sprint_length_days' => 14,
            'wip_limit_per_member' => null,
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