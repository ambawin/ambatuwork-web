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

    public function test_user_can_list_only_pending_invitations_for_them_with_tokens(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create(['email' => 'member@example.com']);
        $firstProject = $this->createProjectForUser($owner);
        $secondProject = Project::create([
            'owner_user_id' => $owner->id,
            'name' => 'Phase 2 Project',
            'description' => 'Second project',
            'product_goal' => 'Ship the second phase.',
            'default_sprint_length_days' => 14,
            'wip_limit_per_member' => null,
            'status' => 'active',
        ]);

        $secondProject->memberships()->create([
            'user_id' => $owner->id,
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($owner);

        $firstInvitation = $this->postJson("/api/v1/projects/{$firstProject->id}/invitations", [
            'email' => 'member@example.com',
            'role' => 'member',
        ]);

        Sanctum::actingAs($invitee);

        $this->postJson("/api/v1/invitations/{$firstInvitation->json('data.token')}/accept")
            ->assertOk();

        Sanctum::actingAs($owner);

        $secondInvitation = $this->postJson("/api/v1/projects/{$secondProject->id}/invitations", [
            'email' => 'member@example.com',
            'role' => 'supervisor',
        ]);

        $this->postJson("/api/v1/projects/{$firstProject->id}/invitations", [
            'email' => 'other@example.com',
            'role' => 'member',
        ]);

        Sanctum::actingAs($invitee);

        $response = $this->getJson('/api/v1/invitations');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.token', $secondInvitation->json('data.token'));
        $response->assertJsonPath('data.0.project.id', $secondProject->id);
        $response->assertJsonPath('data.0.project.name', 'Phase 2 Project');
        $response->assertJsonPath('data.0.project.description', 'Second project');
        $response->assertJsonPath('data.0.project.owner.id', $owner->id);
    }

    public function test_owner_can_list_only_pending_project_invitations(): void
    {
        $owner = User::factory()->create();
        $project = $this->createProjectForUser($owner);

        Sanctum::actingAs($owner);

        $pendingInvitation = $this->postJson("/api/v1/projects/{$project->id}/invitations", [
            'email' => 'member@example.com',
            'role' => 'member',
        ]);

        $revokedInvitation = $this->postJson("/api/v1/projects/{$project->id}/invitations", [
            'email' => 'other@example.com',
            'role' => 'supervisor',
        ]);

        $this->deleteJson("/api/v1/projects/{$project->id}/invitations/{$revokedInvitation->json('data.id')}")
            ->assertOk();

        $response = $this->getJson("/api/v1/projects/{$project->id}/invitations");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $pendingInvitation->json('data.id'));
        $response->assertJsonPath('data.0.token', $pendingInvitation->json('data.token'));
        $response->assertJsonPath('data.0.project.id', $project->id);
        $response->assertJsonPath('data.0.project.owner.id', $owner->id);
    }

    public function test_owner_can_revoke_project_invitation(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create(['email' => 'member@example.com']);
        $project = $this->createProjectForUser($owner);

        Sanctum::actingAs($owner);

        $invitation = $this->postJson("/api/v1/projects/{$project->id}/invitations", [
            'email' => 'member@example.com',
            'role' => 'member',
        ]);

        $deleteResponse = $this->deleteJson("/api/v1/projects/{$project->id}/invitations/{$invitation->json('data.id')}");

        $deleteResponse->assertOk();
        $deleteResponse->assertJsonPath('message', 'Invitation revoked.');

        $this->assertDatabaseHas('project_invitations', [
            'id' => $invitation->json('data.id'),
            'project_id' => $project->id,
            'status' => 'revoked',
        ]);

        Sanctum::actingAs($invitee);

        $acceptResponse = $this->postJson("/api/v1/invitations/{$invitation->json('data.token')}/accept");

        $acceptResponse->assertNotFound();
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