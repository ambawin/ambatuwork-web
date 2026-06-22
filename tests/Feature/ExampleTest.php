<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_manual_landing_returns_a_successful_response(): void
    {
        $response = $this->get('/manual');

        $response->assertStatus(200);
        $response->assertSee('User Manuals');
    }

    public function test_web_manual_returns_a_successful_response(): void
    {
        $response = $this->get('/manual/web');

        $response->assertStatus(200);
        $response->assertSee('Web Version Manual');
    }
}
