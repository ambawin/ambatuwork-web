<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_project(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/projects', [
            'name' => 'Marketing Website Revamp',
            'description' => 'Build and launch the new website.',
            'product_goal' => 'Launch a clear and high-converting website.',
            'default_sprint_length_days' => 14,
            'wip_limit_per_member' => 2,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Marketing Website Revamp');
        $response->assertJsonPath('data.my_role', 'owner');

        $projectId = $response->json('data.id');

        $this->assertDatabaseHas('projects', [
            'id' => $projectId,
            'owner_user_id' => $user->id,
            'name' => 'Marketing Website Revamp',
        ]);

        $this->assertDatabaseHas('project_memberships', [
            'project_id' => $projectId,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
        ]);
    }

    public function test_project_index_returns_only_accessible_projects(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownerProject = Project::create([
            'owner_user_id' => $owner->id,
            'name' => 'Owner Project',
            'description' => null,
            'product_goal' => 'Owner goal',
            'default_sprint_length_days' => 14,
            'wip_limit_per_member' => null,
            'status' => 'active',
        ]);

        $ownerProject->memberships()->create([
            'user_id' => $owner->id,
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $otherProject = Project::create([
            'owner_user_id' => $otherUser->id,
            'name' => 'Other Project',
            'description' => null,
            'product_goal' => 'Other goal',
            'default_sprint_length_days' => 14,
            'wip_limit_per_member' => null,
            'status' => 'active',
        ]);

        $otherProject->memberships()->create([
            'user_id' => $otherUser->id,
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($owner);

        $response = $this->getJson('/api/v1/projects');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $ownerProject->id);
        $response->assertJsonPath('data.0.my_role', 'owner');
    }

    public function test_project_owner_can_view_project_details(): void
    {
        $owner = User::factory()->create();

        $project = Project::create([
            'owner_user_id' => $owner->id,
            'name' => 'Shared Project',
            'description' => 'A project for the team.',
            'product_goal' => 'Ship the first phase.',
            'default_sprint_length_days' => 14,
            'wip_limit_per_member' => 3,
            'status' => 'active',
        ]);

        $project->memberships()->create([
            'user_id' => $owner->id,
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/projects/{$project->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $project->id);
        $response->assertJsonPath('data.my_role', 'owner');
    }
}