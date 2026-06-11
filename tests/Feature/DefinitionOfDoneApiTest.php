<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DefinitionOfDoneApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_and_update_definition_of_done(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectForUser($owner);

        Sanctum::actingAs($owner);

        $showResponse = $this->getJson("/api/v1/projects/{$project->id}/definition-of-done");

        $showResponse->assertOk();
        $showResponse->assertJsonPath('data.title', 'Default Definition of Done');
        $showResponse->assertJsonCount(4, 'data.checklist');

        $updateResponse = $this->patchJson("/api/v1/projects/{$project->id}/definition-of-done", [
            'title' => 'Release Definition of Done',
            'checklist' => [
                'Acceptance criteria are met',
                'Reviewed by another member',
                'No critical bugs remain',
            ],
        ]);

        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('data.title', 'Release Definition of Done');
        $updateResponse->assertJsonCount(3, 'data.checklist');

        $this->assertDatabaseHas('definition_of_dones', [
            'project_id' => $project->id,
            'title' => 'Release Definition of Done',
            'is_active' => true,
        ]);
    }

    public function test_member_cannot_update_definition_of_done(): void
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

        $response = $this->patchJson("/api/v1/projects/{$project->id}/definition-of-done", [
            'title' => 'Not Allowed',
            'checklist' => ['Nope'],
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