<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminCreateUserApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminBearerToken(): string
    {
        $admin = User::factory()->create([
            'username' => 'admin_test',
            'email' => 'admin_test@example.com',
            'password' => Hash::make('secret12aa'),
            'role' => 'Admin',
        ]);

        return JWTAuth::fromUser($admin);
    }

    /** @test */
    public function admin_can_create_doctor_with_specialty(): void
    {
        Mail::fake();
        $token = $this->adminBearerToken();

        $response = $this->postJson(
            '/api/admin/users',
            [
                'name' => 'Dr. Dupont',
                'username' => 'ddupont',
                'email' => 'dupont_doctor@example.com',
                'password' => 'ab12CDEFGH',
                'role' => 'Doctor',
                'specialty' => 'Cardiologie',
            ],
            ['Authorization' => 'Bearer '.$token]
        );

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', [
            'username' => 'ddupont',
            'role' => 'Doctor',
            'specialty' => 'Cardiologie',
        ]);
    }

    /** @test */
    public function doctor_requires_specialty_on_create(): void
    {
        Mail::fake();
        $token = $this->adminBearerToken();

        $response = $this->postJson(
            '/api/admin/users',
            [
                'name' => 'Dr. Sans Spe',
                'username' => 'dspe',
                'email' => 'dspe@example.com',
                'password' => 'ab12CDEFGH',
                'role' => 'Doctor',
            ],
            ['Authorization' => 'Bearer '.$token]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['specialty']);
    }

    /** @test */
    public function doctor_specialty_must_be_at_least_two_chars(): void
    {
        Mail::fake();
        $token = $this->adminBearerToken();

        $response = $this->postJson(
            '/api/admin/users',
            [
                'name' => 'Dr. Court',
                'username' => 'dcourt',
                'email' => 'dcourt@example.com',
                'password' => 'ab12CDEFGH',
                'role' => 'Doctor',
                'specialty' => 'x',
            ],
            ['Authorization' => 'Bearer '.$token]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['specialty']);
    }

    /** @test */
    public function nurse_can_be_created_without_specialty(): void
    {
        Mail::fake();
        $token = $this->adminBearerToken();

        $response = $this->postJson(
            '/api/admin/users',
            [
                'name' => 'Inf. Martin',
                'username' => 'imartin',
                'email' => 'martin_inf@example.com',
                'password' => 'ab12CDEFGH',
                'role' => 'Nurse',
            ],
            ['Authorization' => 'Bearer '.$token]
        );

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', [
            'username' => 'imartin',
            'role' => 'Nurse',
            'specialty' => null,
        ]);
    }

    /** @test */
    public function non_admin_cannot_create_users(): void
    {
        $doctor = User::factory()->create([
            'username' => 'dr_only',
            'email' => 'dr_only@example.com',
            'password' => Hash::make('secret12aa'),
            'role' => 'Doctor',
        ]);
        $token = JWTAuth::fromUser($doctor);

        $response = $this->postJson(
            '/api/admin/users',
            [
                'name' => 'Hack',
                'username' => 'hack',
                'email' => 'hack@example.com',
                'password' => 'ab12CDEFGH',
                'role' => 'Doctor',
                'specialty' => 'Médecine générale',
            ],
            ['Authorization' => 'Bearer '.$token]
        );

        $response->assertStatus(403)->assertJsonPath('success', false);
    }
}
