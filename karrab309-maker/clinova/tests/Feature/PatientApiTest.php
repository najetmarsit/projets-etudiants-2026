<?php

namespace Tests\Feature;

use App\Mail\PatientAdmissionCredentialsMail;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PatientApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function patients_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/patients');
        $response->assertStatus(401);
    }

    /** @test */
    public function doctor_can_list_patients(): void
    {
        $doctor = User::factory()->create(['role' => 'Doctor']);
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($doctor);

        $user = User::factory()->create(['role' => 'Patient']);
        Patient::create([
            'user_id' => $user->id,
            'age' => 30,
            'gender' => 'Male',
            'medical_history' => 'None',
        ]);

        $response = $this->getJson('/api/patients', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function can_show_patient_when_authorized(): void
    {
        $doctor = User::factory()->create(['role' => 'Doctor']);
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($doctor);

        $user = User::factory()->create(['role' => 'Patient']);
        $patient = Patient::create([
            'user_id' => $user->id,
            'age' => 30,
            'gender' => 'Male',
            'medical_history' => 'None',
            'assigned_doctor_id' => $doctor->id,
        ]);

        $response = $this->getJson('/api/patients/' . $patient->id, [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $patient->id);
    }

    /** @test */
    public function patient_show_returns_404_for_missing_id(): void
    {
        $user = User::factory()->create(['role' => 'Doctor']);
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        $response = $this->getJson('/api/patients/99999', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function secretary_can_create_patient_with_auto_generated_credentials_and_chamber_number(): void
    {
        Mail::fake();

        $secretary = User::factory()->create(['role' => 'Secretary']);
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($secretary);

        $payload = [
            'birth_date' => '1995-06-01',
            'national_id' => '08887766',
            'gender' => 'Male',
            'phone' => '0612345678',
            'address' => 'Rue Exemple',
            'appointment_at' => '2026-05-15',
            'current_illness' => 'Consultation cardiologie',
            'new_user' => [
                'name' => 'Patient Test',
                'email' => 'patient.test@example.com',
                // mot de passe auto-généré ; identifiant = CIN
            ],
        ];

        $response = $this->postJson('/api/patients', $payload, [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Patient created successfully')
            ->assertJsonStructure(['data' => ['id', 'user', 'chamber_number']]);

        $patientId = (int) $response->json('data.id');
        $patient = Patient::with('user')->findOrFail($patientId);

        $this->assertNotNull($patient->user);
        $this->assertSame('08887766', $patient->user->username);
        $this->assertSame('08887766', $patient->national_id);
        $this->assertSame('Consultation cardiologie', $patient->current_illness);
        $this->assertNotEmpty($patient->chamber_number);
        $this->assertStringStartsWith('CH-', (string) $patient->chamber_number);

        Mail::assertSent(PatientAdmissionCredentialsMail::class, function (PatientAdmissionCredentialsMail $mail) use ($patient) {
            return $mail->user->id === $patient->user->id
                && $mail->chamberNumber === (string) $patient->chamber_number;
        });
    }

    /** @test */
    public function secretary_can_link_existing_patient_user_and_sets_username_equal_to_national_id(): void
    {
        $secretary = User::factory()->create(['role' => 'Secretary']);
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($secretary);

        $patientUser = User::factory()->create([
            'role' => 'Patient',
            'username' => 'old_mobile_login',
            'password' => 'password12x',
        ]);

        $payload = [
            'user_id' => $patientUser->id,
            'national_id' => 'CI990011',
            'birth_date' => '1988-01-01',
            'gender' => 'Female',
            'phone' => '0622001100',
            'current_illness' => 'Douleur persistante',
            'appointment_at' => '2026-06-20',
        ];

        $response = $this->postJson('/api/patients', $payload, [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $patientUser->refresh();

        $this->assertSame('CI990011', $patientUser->username);

        $patient = Patient::where('user_id', $patientUser->id)->first();
        $this->assertNotNull($patient);
        $this->assertSame('CI990011', $patient->national_id);
        $this->assertSame('Douleur persistante', $patient->current_illness);
        $rdv = $patient->appointment_at;
        $this->assertNotNull($rdv);
        $this->assertStringStartsWith('2026-06-20', is_string($rdv) ? $rdv : $rdv->toDateString());
    }

    /** @test */
    public function secretary_cannot_link_patient_if_cin_already_another_accounts_username(): void
    {
        $secretary = User::factory()->create(['role' => 'Secretary']);
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($secretary);

        User::factory()->create([
            'role' => 'Patient',
            'username' => 'CONFLICT999',
            'password' => 'password99x',
        ]);

        $toLink = User::factory()->create([
            'role' => 'Patient',
            'username' => 'other_login',
            'password' => 'password99x',
        ]);

        $payload = [
            'user_id' => $toLink->id,
            'national_id' => 'CONFLICT999',
            'birth_date' => '1992-06-06',
            'gender' => 'Male',
        ];

        $response = $this->postJson('/api/patients', $payload, [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.national_id.0', 'Ce CIN est déjà utilisé comme identifiant de connexion par un autre compte.');
    }

    /** @test */
    public function secretary_can_list_all_patients_but_does_not_receive_sensitive_fields(): void
    {
        $secretary = User::factory()->create(['role' => 'Secretary']);
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($secretary);

        $u1 = User::factory()->create(['role' => 'Patient']);
        $p1 = Patient::create([
            'user_id' => $u1->id,
            'age' => 30,
            'gender' => 'Male',
            'first_name' => 'A',
            'last_name' => 'B',
            'diagnosis' => 'Sensitive diagnosis',
            'doctor_observations' => 'Sensitive obs',
        ]);

        $u2 = User::factory()->create(['role' => 'Patient']);
        $p2 = Patient::create([
            'user_id' => $u2->id,
            'age' => 40,
            'gender' => 'Female',
            'first_name' => 'C',
            'last_name' => 'D',
        ]);

        $response = $this->getJson('/api/patients', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $ids = array_map(fn ($row) => $row['id'] ?? null, $response->json('data') ?? []);
        $this->assertContains($p1->id, $ids);
        $this->assertContains($p2->id, $ids);

        $first = collect($response->json('data'))->firstWhere('id', $p1->id);
        $this->assertIsArray($first);
        $this->assertArrayNotHasKey('diagnosis', $first);
        $this->assertArrayNotHasKey('doctor_observations', $first);
        $this->assertArrayNotHasKey('medical_history', $first);
    }
}
