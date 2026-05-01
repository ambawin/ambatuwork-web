<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_invite_a_user_by_email(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectForUser($owner);

        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/projects/{$project->id}/invitations", [
            'email' => 'member@example.com',
            'role' => 'member',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.email', 'member@example.com');
        $response->assertJsonPath('data.role', 'member');
        $response->assertJsonPath('data.status', 'pending');
        $response->assertJsonStructure(['data' => ['token']]);

        $this->assertDatabaseHas('project_invitations', [
            'project_id' => $project->id,
            'email' => 'member@example.com',
            'role' => 'member',
            'status' => 'pending',
        ]);
    }

    public function test_invited_user_can_accept_invitation(): void
    {
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
        $response->assertJsonPath('message', 'Invitation accepted.');
        $response->assertJsonPath('data.id', $project->id);
        $response->assertJsonPath('data.my_role', 'member');

        $this->assertDatabaseHas('project_invitations', [
            'project_id' => $project->id,
            'email' => 'member@example.com',
            'status' => 'accepted',
            'accepted_by_user_id' => $invitee->id,
        ]);

        $this->assertDatabaseHas('project_memberships', [
            'project_id' => $project->id,
            'user_id' => $invitee->id,
            'role' => 'member',
            'status' => 'active',
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