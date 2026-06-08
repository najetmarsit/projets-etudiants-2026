<?php

namespace Tests\Feature;

use App\Models\HealthIndicator;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class HealthIndicatorNurseApiTest extends TestCase
{
    use RefreshDatabase;

    private function makePatient(): Patient
    {
        $u = User::factory()->create(['role' => 'Patient']);

        return Patient::create([
            'user_id' => $u->id,
            'age' => 35,
            'gender' => 'Male',
            'medical_history' => 'Test',
        ]);
    }

    /** @test */
    public function patient_cannot_create_health_indicator(): void
    {
        $patient = $this->makePatient();
        $token = JWTAuth::fromUser(User::findOrFail($patient->user_id));

        $response = $this->postJson('/api/health-indicators', [
            'patient_id' => $patient->id,
            'heart_rate' => 72,
            'temperature' => 36.8,
            'blood_glucose' => 5.2,
            'blood_pressure_systolic' => 120,
            'blood_pressure_diastolic' => 78,
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(403);
        $this->assertSame(0, HealthIndicator::count());
    }

    /** @test */
    public function doctor_cannot_create_health_indicator(): void
    {
        $doctor = User::factory()->create(['role' => 'Doctor']);
        $patient = $this->makePatient();
        $token = JWTAuth::fromUser($doctor);

        $response = $this->postJson('/api/health-indicators', [
            'patient_id' => $patient->id,
            'heart_rate' => 72,
            'temperature' => 36.8,
            'blood_glucose' => 5.2,
            'blood_pressure_systolic' => 120,
            'blood_pressure_diastolic' => 78,
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function nurse_can_create_vitals_for_patient(): void
    {
        $nurse = User::factory()->create(['role' => 'Nurse']);
        $patient = $this->makePatient();
        $token = JWTAuth::fromUser($nurse);

        $response = $this->postJson('/api/health-indicators', [
            'patient_id' => $patient->id,
            'heart_rate' => 76,
            'temperature' => 36.7,
            'blood_glucose' => 5.4,
            'blood_pressure_systolic' => 118,
            'blood_pressure_diastolic' => 76,
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.patient_id', $patient->id)
            ->assertJsonPath('data.heart_rate', 76)
            ->assertJsonPath('data.recorded_by_user_id', $nurse->id);

        $this->assertDatabaseHas('health_indicators', [
            'patient_id' => $patient->id,
            'heart_rate' => 76,
            'recorded_by_user_id' => $nurse->id,
        ]);
    }

    /** @test */
    public function nurse_cannot_store_invalid_blood_pressure_order(): void
    {
        $nurse = User::factory()->create(['role' => 'Nurse']);
        $patient = $this->makePatient();
        $token = JWTAuth::fromUser($nurse);

        $response = $this->postJson('/api/health-indicators', [
            'patient_id' => $patient->id,
            'heart_rate' => 76,
            'temperature' => 36.7,
            'blood_glucose' => 5.4,
            'blood_pressure_systolic' => 80,
            'blood_pressure_diastolic' => 90,
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function patient_can_list_own_indicators_read_only(): void
    {
        $nurse = User::factory()->create(['role' => 'Nurse']);
        $patient = $this->makePatient();
        $nurseToken = JWTAuth::fromUser($nurse);
        $this->postJson('/api/health-indicators', [
            'patient_id' => $patient->id,
            'heart_rate' => 70,
            'temperature' => 36.5,
            'blood_glucose' => 5.0,
            'blood_pressure_systolic' => 110,
            'blood_pressure_diastolic' => 70,
        ], ['Authorization' => 'Bearer '.$nurseToken])->assertStatus(201);

        $patientToken = JWTAuth::fromUser(User::findOrFail($patient->user_id));
        $response = $this->getJson('/api/health-indicators', [
            'Authorization' => 'Bearer '.$patientToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertCount(1, $response->json('data'));
    }
}
