<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FcmDeviceTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_register_fcm_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/auth/device-token', [
            'fcm_token' => 'fcm-test-token-12345',
            'device_name' => 'Pixel 9',
        ]);

        $response->assertOk();
        $response->assertJson(['message' => 'Device token registered.']);

        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $user->id,
            'fcm_token' => 'fcm-test-token-12345',
            'device_name' => 'Pixel 9',
        ]);
    }

    public function test_authenticated_user_can_update_device_name_for_existing_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Pre-create token
        $user->deviceTokens()->create([
            'fcm_token' => 'fcm-test-token-12345',
            'device_name' => 'Old Device',
        ]);

        $response = $this->putJson('/api/v1/auth/device-token', [
            'fcm_token' => 'fcm-test-token-12345',
            'device_name' => 'New Device',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $user->id,
            'fcm_token' => 'fcm-test-token-12345',
            'device_name' => 'New Device',
        ]);

        $this->assertEquals(1, $user->deviceTokens()->count());
    }

    public function test_validation_fails_when_fcm_token_is_missing(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/auth/device-token', [
            'device_name' => 'Pixel 9',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['fcm_token']);
    }

    public function test_unauthenticated_user_cannot_register_token(): void
    {
        $response = $this->putJson('/api/v1/auth/device-token', [
            'fcm_token' => 'some-token',
        ]);

        $response->assertStatus(401);
    }
}
