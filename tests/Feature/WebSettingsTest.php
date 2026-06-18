<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebSettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that guests cannot access the settings page and are redirected to login.
     */
    public function test_guest_cannot_access_settings(): void
    {
        $response = $this->get('/settings');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /**
     * Test that authenticated users can access the settings page.
     */
    public function test_authenticated_user_can_access_settings(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/settings');

        $response->assertStatus(200);
        $response->assertSeeLivewire('settings');
    }

    /**
     * Test that the settings page displays the active project details.
     */
    public function test_settings_page_displays_active_project_details(): void
    {
        $user = User::factory()->create();
        
        $project = Project::create([
            'owner_user_id' => $user->id,
            'name' => 'Settings Test Project',
            'description' => 'Test project description',
            'product_goal' => 'Test project goal',
            'default_sprint_length_days' => 14,
            'status' => 'active',
        ]);

        $project->memberships()->create([
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_project_id' => $project->id])
            ->get('/settings');

        $response->assertStatus(200);
        $response->assertSee('Settings Test Project');
        $response->assertSee('Test project description');
        $response->assertSee('Test project goal');
    }
}
