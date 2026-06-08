<?php

namespace Database\Seeders;

use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\HealthIndicator;
use App\Models\LabAppointment;
use App\Models\Message;
use App\Models\NursingNote;
use App\Models\Operation;
use App\Models\Patient;
use App\Models\PatientBillableItem;
use App\Models\Payment;
use App\Models\Report;
use App\Models\SpecialistAppointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration alignées sur les scénarios « hôpital Clinova »
 * (noms réalistes, dossiers, alertes, RDV, facturation).
 *
 * Idempotent : safe à relancer (updateOrCreate / firstOrCreate).
 */
class ClinovaHospitalScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('CLINOVA_DEFAULT_PASSWORD', 'password123');

        $doctor = User::updateOrCreate(
            ['username' => 'doctor'],
            [
                'name' => 'Dr Mourad Benali',
                'email' => 'mourad.benali@clinova.local',
                'password' => $password,
                'role' => 'Doctor',
                'specialty' => 'Chirurgie digestive',
                'locale' => 'fr',
            ]
        );

        $nurse = User::updateOrCreate(
            ['username' => 'nurse'],
            [
                'name' => 'Khadija Idrissi',
                'email' => 'khadija.idrissi@clinova.local',
                'password' => $password,
                'role' => 'Nurse',
                'locale' => 'fr',
            ]
        );

        User::updateOrCreate(
            ['username' => 'secretary'],
            [
                'name' => 'Hanane Filali',
                'email' => 'hanane.filali@clinova.local',
                'password' => $password,
                'role' => 'Secretary',
                'locale' => 'fr',
            ]
        );

        User::updateOrCreate(
            ['username' => 'accountant'],
            [
                'name' => 'Imane Cherkaoui',
                'email' => 'imane.cherkaoui@clinova.local',
                'password' => $password,
                'role' => 'Accountant',
                'locale' => 'fr',
            ]
        );

        // --- Patient : Mme Fatma El Mansouri (post-op digestive) ---
        $uFatma = User::updateOrCreate(
            ['username' => 'fatma.elmansouri'],
            [
                'name' => 'Fatma El Mansouri',
                'email' => 'fatma.elmansouri@clinova.local',
                'password' => $password,
                'role' => 'Patient',
                'locale' => 'fr',
            ]
        );
        $pFatma = Patient::updateOrCreate(
            ['user_id' => $uFatma->id],
            [
                'first_name' => 'Fatma',
                'last_name' => 'El Mansouri',
                'birth_date' => Carbon::parse('1967-03-12'),
                'national_id' => 'CLINOFATMA67',
                'age' => 58,
                'gender' => 'Female',
                'phone' => '+212 6 12 34 56 78',
                'address' => 'Quartier Hassan, Rabat',
                'medical_history' => 'HTA légère. Colectomie segmentaire J+2. Pas d\'allergie médicamenteuse connue.',
                'status' => 'hospitalized',
                'room_number' => '302',
                'bed_number' => 'A',
                'chamber_number' => '302-A',
                'diagnosis' => 'Tumeur colique traitée — suivi post-opératoire.',
                'current_illness' => 'Douleur abdominale résiduelle, surveillance pansement.',
                'prescribed_treatment' => 'Antalgiques paliers, perfusion cristalloïdes, prophylaxie thrombotique.',
                'doctor_observations' => 'Cicatrisation correcte. Réévaluation antalgique si EVA > 4.',
                'assigned_doctor_id' => $doctor->id,
                'assigned_nurse_id' => $nurse->id,
                'admission_at' => Carbon::now()->subDays(2),
                'billing_total_due' => 18500.00,
                'billing_breakdown' => [
                    ['label' => 'Hospitalisation J2', 'amount' => 12000],
                    ['label' => 'Bloc & salle de réveil', 'amount' => 4500],
                    ['label' => 'Pharmacie hospitalière', 'amount' => 2000],
                ],
            ]
        );

        Operation::firstOrCreate(
            ['patient_id' => $pFatma->id, 'operation_type' => 'Colectomie segmentaire'],
            [
                'doctor_id' => $doctor->id,
                'notes' => 'Intervention programmée. Suites simples, surveillance douleur et transit.',
                'operation_date' => Carbon::now()->subDays(2)->setTime(9, 30),
            ]
        );

        HealthIndicator::updateOrCreate(
            [
                'patient_id' => $pFatma->id,
                'recorded_at' => Carbon::parse('2026-05-28 14:00:00'),
            ],
            [
                'heart_rate' => 78,
                'blood_glucose' => 5.6,
                'blood_pressure_systolic' => 128,
                'blood_pressure_diastolic' => 78,
                'pain_level' => 7,
                'temperature' => 37.4,
                'dressing_status' => 'Good',
                'recorded_by_user_id' => $nurse->id,
            ]
        );

        NursingNote::firstOrCreate(
            [
                'patient_id' => $pFatma->id,
                'nurse_user_id' => $nurse->id,
                'note' => 'Pansement propre, pas de rougœur péri-lésionnelle. EVA 7 malgré analgésie — médecin informé via alerte.',
            ]
        );

        Alert::firstOrCreate(
            [
                'patient_id' => $pFatma->id,
                'message' => 'Douleur élevée : 7/10 — réévaluation antalgique demandée.',
            ],
            [
                'assigned_doctor_id' => $doctor->id,
                'indicator_type' => 'pain_level',
                'value' => '7',
                'priority' => 'urgent',
                'status' => AlertStatus::Sent,
                'created_at' => Carbon::now()->subHours(4),
            ]
        );

        SpecialistAppointment::updateOrCreate(
            [
                'patient_id' => $pFatma->id,
                'note' => 'Contrôle post-opératoire — Dr Benali.',
            ],
            [
                'specialty' => 'Chirurgie digestive',
                'scheduled_at' => Carbon::parse('2026-06-20 10:00:00'),
                'status' => 'planned',
                'created_by' => $doctor->id,
            ]
        );

        PatientBillableItem::firstOrCreate(
            [
                'patient_id' => $pFatma->id,
                'label' => 'Forfait hospitalisation J2 (scénario démo)',
            ],
            [
                'kind' => 'visit',
                'amount' => 12000,
                'performed_at' => Carbon::now()->subDay(),
                'created_by_user_id' => $doctor->id,
            ]
        );

        Payment::firstOrCreate(
            ['receipt_number' => '550e8400-e29b-41d4-a716-446655440001'],
            [
                'patient_id' => $pFatma->id,
                'recorded_by' => User::where('username', 'accountant')->first()?->id,
                'payer_name' => 'Fatma El Mansouri',
                'national_id' => 'CLINOFATMA67',
                'email' => 'fatma.elmansouri@clinova.local',
                'phone' => '+212 6 12 34 56 78',
                'city' => 'Rabat',
                'file_label' => 'Acompte hospitalisation',
                'total_amount' => 18500,
                'amount' => 18500,
                'currency' => 'MAD',
                'paid_at' => Carbon::parse('2026-05-29 10:00:00'),
                'status' => 'paid',
                'provider' => 'manual',
            ]
        );

        // --- Patient : Sara Alami (ambulatoire) ---
        $uSara = User::updateOrCreate(
            ['username' => 'sara.alami'],
            [
                'name' => 'Sara Alami',
                'email' => 'sara.alami@clinova.local',
                'password' => $password,
                'role' => 'Patient',
                'locale' => 'fr',
            ]
        );
        $pSara = Patient::updateOrCreate(
            ['user_id' => $uSara->id],
            [
                'first_name' => 'Sara',
                'last_name' => 'Alami',
                'birth_date' => Carbon::parse('1996-08-21'),
                'national_id' => 'CLINOSARA96',
                'age' => 29,
                'gender' => 'Female',
                'phone' => '+212 6 22 11 00 99',
                'medical_history' => 'Appendicectomie récente — suivi ambulatoire.',
                'status' => 'outpatient',
                'assigned_doctor_id' => $doctor->id,
                'assigned_nurse_id' => $nurse->id,
                'diagnosis' => 'Post-appendicectomie sans complication.',
            ]
        );

        LabAppointment::updateOrCreate(
            [
                'patient_id' => $pSara->id,
                'patient_note' => 'Prise de sang à jeun pour bilan post-opératoire.',
            ],
            [
                'scheduled_at' => Carbon::parse('2026-06-12 08:15:00'),
                'status' => 'pending',
            ]
        );

        SpecialistAppointment::updateOrCreate(
            [
                'patient_id' => $pSara->id,
                'note' => 'Retrait de fils et contrôle cicatrice.',
            ],
            [
                'specialty' => 'Médecine générale',
                'scheduled_at' => Carbon::parse('2026-06-18 14:30:00'),
                'status' => 'confirmed',
                'created_by' => User::where('username', 'secretary')->value('id'),
            ]
        );

        // --- Patient : Nissrine Berrada (diabète, glycémie / alerte) ---
        $uNissrine = User::updateOrCreate(
            ['username' => 'nissrine.berra'],
            [
                'name' => 'Nissrine Berrada',
                'email' => 'nissrine.berra@clinova.local',
                'password' => $password,
                'role' => 'Patient',
                'locale' => 'fr',
            ]
        );
        $pNissrine = Patient::updateOrCreate(
            ['user_id' => $uNissrine->id],
            [
                'first_name' => 'Nissrine',
                'last_name' => 'Berrada',
                'birth_date' => Carbon::parse('1974-11-02'),
                'national_id' => 'CLINONISS74',
                'age' => 51,
                'gender' => 'Female',
                'phone' => '+212 6 55 44 33 22',
                'medical_history' => 'Diabète type 2 — metformine. Suivi endocrinologie.',
                'status' => 'outpatient',
                'assigned_doctor_id' => $doctor->id,
                'diagnosis' => 'Équilibre glycémique à optimiser.',
            ]
        );

        HealthIndicator::updateOrCreate(
            [
                'patient_id' => $pNissrine->id,
                'recorded_at' => Carbon::parse('2026-05-29 09:30:00'),
            ],
            [
                'heart_rate' => 88,
                'blood_glucose' => 12.4,
                'blood_pressure_systolic' => 132,
                'blood_pressure_diastolic' => 84,
                'pain_level' => 2,
                'temperature' => 36.9,
                'dressing_status' => 'Good',
                'recorded_by_user_id' => $nurse->id,
            ]
        );

        Alert::firstOrCreate(
            [
                'patient_id' => $pNissrine->id,
                'message' => 'Glycémie hors cible : 12,4 mmol/L — contrôle endocrino à prévoir.',
            ],
            [
                'assigned_doctor_id' => $doctor->id,
                'indicator_type' => 'blood_glucose',
                'value' => '12.4',
                'priority' => 'normal',
                'status' => AlertStatus::Sent,
                'created_at' => Carbon::now()->subHours(1),
            ]
        );

        // --- Patient : Ahmed Tazi (consultation pneumo — dossier complet) ---
        $uAhmed = User::updateOrCreate(
            ['username' => 'ahmed.tazi'],
            [
                'name' => 'Ahmed Tazi',
                'email' => 'ahmed.tazi@clinova.local',
                'password' => $password,
                'role' => 'Patient',
                'locale' => 'fr',
            ]
        );
        $pAhmed = Patient::updateOrCreate(
            ['user_id' => $uAhmed->id],
            [
                'first_name' => 'Ahmed',
                'last_name' => 'Tazi',
                'birth_date' => Carbon::parse('1983-05-14'),
                'national_id' => 'CLINOAHMED83',
                'age' => 42,
                'gender' => 'Male',
                'phone' => '+212 6 77 88 90 12',
                'medical_history' => 'Asthme léger. Tabac sevré depuis 2 ans.',
                'status' => 'outpatient',
                'assigned_doctor_id' => $doctor->id,
                'diagnosis' => 'BPCO débutante — suivi pneumologique.',
            ]
        );

        Message::firstOrCreate(
            [
                'sender_id' => $uAhmed->id,
                'receiver_id' => $doctor->id,
                'content' => 'Bonjour Docteur, toux grasse depuis 5 jours sans fièvre. Je joins les symptômes pour avis.',
            ],
            ['read_status' => false, 'created_at' => Carbon::now()->subHours(6)]
        );

        Message::firstOrCreate(
            [
                'sender_id' => $doctor->id,
                'receiver_id' => $uAhmed->id,
                'content' => 'Bonjour Ahmed, merci pour le message. Prévoyez une spirométrie ambulatoire ; si dyspnée augmente, aux urgences.',
            ],
            ['read_status' => true, 'created_at' => Carbon::now()->subHours(5)]
        );

        Report::firstOrCreate(
            [
                'patient_id' => $pAhmed->id,
                'report_type' => 'Compte-rendu consultation pneumologie',
            ],
            [
                'generated_by' => $doctor->id,
                'content' => "Patient Ahmed Tazi — 42 ans.\nExamen clinique : SAO2 97 % air ambiant, MV diminué bases.\nConduite : spirométrie, arrêt tabac renforcé, contrôle 6 semaines.",
                'created_at' => Carbon::now()->subDay(),
            ]
        );

        // Message patient → médecin (Fatma)
        Message::firstOrCreate(
            [
                'sender_id' => $uFatma->id,
                'receiver_id' => $doctor->id,
                'content' => 'Docteur, la douleur au ventre est encore forte ce soir malgré les médicaments.',
            ],
            ['read_status' => false, 'created_at' => Carbon::now()->subHours(3)]
        );
    }
}
