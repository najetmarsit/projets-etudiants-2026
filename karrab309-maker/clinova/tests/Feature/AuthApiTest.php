<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function register_is_forbidden_for_public(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'Patient',
        ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function login_returns_token_for_valid_credentials(): void
    {
        $user = User::factory()->create([
            'username' => 'doctor1',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'doctor1',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'message', 'user', 'token'])
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function login_fails_for_invalid_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'unknown',
            'password' => 'wrong',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function me_returns_user_when_authenticated(): void
    {
        $user = User::factory()->create();
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        $response = $this->getJson('/api/auth/me', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.id', $user->id);
    }

    /** @test */
    public function me_returns_401_when_not_authenticated(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }
}
