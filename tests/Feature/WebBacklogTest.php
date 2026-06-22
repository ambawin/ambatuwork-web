<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\BacklogItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebBacklogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that guests cannot access the edit backlog page and are redirected to login.
     */
    public function test_guest_cannot_access_edit_backlog_page(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectForUser($owner);
        $item = $project->backlogItems()->create([
            'title' => 'Test Backlog Item',
            'type' => 'story',
            'status' => 'backlog',
            'priority' => 'medium',
            'estimate_points' => 5,
            'created_by_user_id' => $owner->id,
        ]);

        $response = $this->get("/backlog/{$item->id}/edit");

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /**
     * Test that project owners can access the edit backlog page.
     */
    public function test_owner_can_access_edit_backlog_page(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectForUser($owner);
        $item = $project->backlogItems()->create([
            'title' => 'Test Backlog Item',
            'type' => 'story',
            'status' => 'backlog',
            'priority' => 'medium',
            'estimate_points' => 5,
            'created_by_user_id' => $owner->id,
        ]);

        $response = $this->actingAs($owner)->get("/backlog/{$item->id}/edit");

        $response->assertStatus(200);
        $response->assertSeeLivewire('edit-backlog');
        $response->assertSee('Test Backlog Item');
    }

    /**
     * Test that non-members / supervisors cannot access the edit backlog page.
     */
    public function test_supervisor_cannot_access_edit_backlog_page(): void
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

        $item = $project->backlogItems()->create([
            'title' => 'Test Backlog Item',
            'type' => 'story',
            'status' => 'backlog',
            'priority' => 'medium',
            'estimate_points' => 5,
            'created_by_user_id' => $owner->id,
        ]);

        // A supervisor has read access but cannot manage the backlog, so they should be redirected
        $response = $this->actingAs($supervisor)
            ->withSession(['active_project_id' => $project->id])
            ->get("/backlog/{$item->id}/edit");

        $response->assertStatus(302);
        $response->assertRedirect('/backlog');
    }

    private function createProjectForUser(User $user): Project
    {
        $project = Project::create([
            'owner_user_id' => $user->id,
            'name' => 'Web Backlog Test Project',
            'description' => null,
            'product_goal' => 'Ship web backlog.',
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
}
