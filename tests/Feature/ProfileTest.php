<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that guests cannot access the profile page and are redirected to login.
     */
    public function test_guest_cannot_access_profile(): void
    {
        $response = $this->get('/profile');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /**
     * Test that authenticated users can access the profile page and see their details.
     */
    public function test_authenticated_user_can_access_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Profile Test User',
            'email' => 'profiletest@example.com',
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertStatus(200);
        $response->assertSeeLivewire('profile');
        $response->assertSee('Profile Test User');
        $response->assertSee('profiletest@example.com');
    }
}
