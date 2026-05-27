<?php

namespace Tests\Feature;

use App\Actions\Auth\VerifyGoogleIdTokenAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class WebAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that guests cannot access the dashboard and are redirected to login.
     */
    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /**
     * Test that guests can access the login page.
     */
    public function test_guest_can_access_login(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('login');
    }

    /**
     * Test that authenticated users are redirected to dashboard when accessing login.
     */
    public function test_authenticated_user_cannot_access_login(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        $response->assertStatus(302);
        $response->assertRedirect('/dashboard');
    }

    /**
     * Test that authenticated users can access the dashboard.
     */
    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSeeLivewire('dashboard');
    }

    /**
     * Test secure logout logic removes session.
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * Test successful Google OAuth Sign-in callback.
     */
    public function test_google_callback_authenticates_user_and_redirects(): void
    {
        // Mock Google Identity Token Verification Action
        $this->mock(VerifyGoogleIdTokenAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('execute')
                ->with('test-google-token-123')
                ->once()
                ->andReturn([
                    'google_id' => 'google-user-12345',
                    'email' => 'testuser@gmail.com',
                    'name' => 'John Doe',
                    'avatar_url' => 'https://lh3.googleusercontent.com/avatar',
                    'email_verified' => true,
                ]);
        });

        // Submit OAuth callback payload
        $response = $this->postJson('/auth/google/callback', [
            'id_token' => 'test-google-token-123',
        ]);

        // Assert JSON success state and correct redirect URL
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'redirect_url' => route('dashboard'),
        ]);

        // Verify session user and DB updates
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'google_id' => 'google-user-12345',
            'email' => 'testuser@gmail.com',
            'name' => 'John Doe',
            'avatar_url' => 'https://lh3.googleusercontent.com/avatar',
        ]);
    }

    /**
     * Test Google Auth Callback fails with invalid input token.
     */
    public function test_google_callback_fails_without_token(): void
    {
        $response = $this->postJson('/auth/google/callback', []);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Google ID token is required.',
        ]);
        
        $this->assertGuest();
    }
}
