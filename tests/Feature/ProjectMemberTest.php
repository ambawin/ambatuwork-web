<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_list_update_and_remove_members(): void
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

        Sanctum::actingAs($owner);

        $indexResponse = $this->getJson("/api/v1/projects/{$project->id}/members");

        $indexResponse->assertOk();
        $indexResponse->assertJsonCount(2, 'data');

        $updateResponse = $this->patchJson("/api/v1/projects/{$project->id}/members/{$member->id}", [
            'role' => 'supervisor',
        ]);

        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('data.role', 'supervisor');

        $this->assertDatabaseHas('project_memberships', [
            'project_id' => $project->id,
            'user_id' => $member->id,
            'role' => 'supervisor',
            'status' => 'active',
        ]);

        $deleteResponse = $this->deleteJson("/api/v1/projects/{$project->id}/members/{$member->id}");

        $deleteResponse->assertOk();
        $deleteResponse->assertJsonPath('message', 'Member removed from project.');

        $this->assertDatabaseHas('project_memberships', [
            'project_id' => $project->id,
            'user_id' => $member->id,
            'status' => 'removed',
        ]);
    }

    private function createProjectForUser(User $user): Project
    {
        $project = Project::create([
            'owner_user_id' => $user->id,
            'name' => 'Phase 1 Project',
            'description' => null,
            'product_goal' => 'Ship the first phase.',
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

        return $project;
    }
}