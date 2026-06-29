<?php

namespace Tests\Feature;

use Tests\TestCase;

class BootstrapTest extends TestCase
{
    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_api_requests_receive_json_auth_errors(): void
    {
        $this->getJson('/api/user')
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_missing_api_routes_return_json_not_found_errors(): void
    {
        $this->getJson('/api/this-route-does-not-exist')
            ->assertNotFound()
            ->assertJson([
                'message' => 'Resource not found.',
            ]);
    }

    public function test_auth_routes_are_rate_limited(): void
    {
        foreach (range(1, 10) as $attempt) {
            $this->postJson('/api/auth/login', [
                'email' => 'missing@example.com',
                'password' => 'password',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/auth/login', [
            'email' => 'missing@example.com',
            'password' => 'password',
        ])
            ->assertStatus(429)
            ->assertJsonStructure(['message']);
    }
}
