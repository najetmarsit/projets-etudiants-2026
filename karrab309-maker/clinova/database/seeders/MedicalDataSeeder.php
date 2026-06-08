<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Patient;
use App\Models\Operation;
use App\Models\HealthIndicator;
use App\Models\Alert;
use App\Models\Message;
use App\Models\Report;
use App\Enums\AlertStatus;
use Carbon\Carbon;

class MedicalDataSeeder extends Seeder
{
    public function run(): void
    {
        // Mot de passe en clair : le cast 'hashed' du modèle User le hash automatiquement
        $admin = User::updateOrCreate(['username' => 'admin'], [
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => 'password123',
            'role' => 'Admin',
        ]);

        $doctor = User::updateOrCreate(['username' => 'doctor'], [
            'name' => 'Dr. Lafi',
            'email' => 'lafi@gmail.com',
            'password' => 'password123',
            'role' => 'Doctor',
            'specialty' => 'Médecine générale',
        ]);

        $nurse = User::updateOrCreate(['username' => 'nurse'], [
            'name' => 'Infirmier démo',
            'email' => 'nurse@local.test',
            'password' => 'password123',
            'role' => 'Nurse',
        ]);

        // Compte laboratoire (portail /lab) — mot de passe : password123
        User::updateOrCreate(['username' => 'laboratoire'], [
            'name' => 'Laboratoire Central',
            'email' => 'labo@gmail.com',
            'password' => 'password123',
            'role' => 'Laboratory',
        ]);

        // Compte demandé à l’inscription (email du test utilisateur)
        User::updateOrCreate(['email' => 'labo44@gmail.com'], [
            'name' => 'Laboratoire',
            'username' => 'labo44',
            'password' => 'password123',
            'role' => 'Laboratory',
        ]);

        $patientUser1 = User::updateOrCreate(['username' => 'mohamed karrab'], [
            'name' => 'mohamed karrab',
            'email' => 'mohamedkarrab@gmail.com',
            'password' => 'password123',
            'role' => 'Patient',
        ]);

        $patientUser2 = User::updateOrCreate(['username' => 'meriem boussaid'], [
            'name' => 'meriem boussaid',
            'email' => 'meriemboussaid@gmail.com',
            'password' => 'password123',
            'role' => 'Patient',
        ]);

        $patient1 = Patient::updateOrCreate(
            ['user_id' => $patientUser1->id],
            [
                'national_id' => 'mohamed123',
                'age' => 50,
                'gender' => 'Male',
                'medical_history' => 'Chirurgie appendicectomie. Pas d\'allergies connues.',
            ]
        );

        $patient2 = Patient::updateOrCreate(
            ['user_id' => $patientUser2->id],
            [
                'national_id' => 'mariem123',
                'age' => 28,
                'gender' => 'Female',
                'medical_history' => 'Asthme contrôlé. Arthroscopie genou.',
            ]
        );

        // Comptes rôle Patient sans fiche médicale : visibles dans « Nouveau patient » du dashboard
        // (inscription mobile ou comptes de test en attente d’assignation par un médecin).
        User::updateOrCreate(['username' => 'adem'], [
            'name' => 'adem',
            'email' => 'adem@gmail.com',
            'password' => 'password123',
            'role' => 'Patient',
        ]);
        User::updateOrCreate(['username' => 'sabrine'], [
            'name' => 'sabrine',
            'email' => 'sabrine@gmail.com',
            'password' => 'password123',
            'role' => 'Patient',
        ]);

        Operation::firstOrCreate(
            ['patient_id' => $patient1->id, 'operation_type' => 'Appendicectomie'],
            [
                'doctor_id' => $doctor->id,
                'notes' => 'Intervention en urgence. Suites opératoires simples.',
                'operation_date' => Carbon::now()->subDays(21),
            ]
        );

        Operation::firstOrCreate(
            ['patient_id' => $patient2->id, 'operation_type' => 'Arthroscopie genou'],
            [
                'doctor_id' => $doctor->id,
                'notes' => 'Réparation ménisque. Récupération en cours.',
                'operation_date' => Carbon::now()->subWeeks(3),
            ]
        );

        // Jean Martin : évolution sur plusieurs jours pour graphique (douleur + température)
        $base = Carbon::now()->subDays(7)->startOfDay();
        $painLevels = [4, 3, 3, 2, 2, 1, 2, 1, 2, 1];
        $temps = [37.8, 37.5, 37.2, 37.0, 36.9, 36.8, 37.0, 36.8, 37.2, 38.2];
        for ($i = 0; $i < 10; $i++) {
            HealthIndicator::create([
                'patient_id' => $patient1->id,
                'heart_rate' => 68 + ($i % 18),
                'blood_glucose' => round(5.0 + $i * 0.08, 2),
                'blood_pressure_systolic' => 118 + ($i % 8),
                'blood_pressure_diastolic' => 74 + ($i % 6),
                'pain_level' => $painLevels[$i],
                'temperature' => $temps[$i],
                'dressing_status' => $i >= 8 ? 'Needs Change' : 'Good',
                'recorded_at' => $base->copy()->addDays(intval($i / 2))->addHours($i * 2),
                'recorded_by_user_id' => $nurse->id,
            ]);
        }

        HealthIndicator::create([
            'patient_id' => $patient2->id,
            'heart_rate' => 102,
            'blood_glucose' => 6.2,
            'blood_pressure_systolic' => 128,
            'blood_pressure_diastolic' => 82,
            'pain_level' => 5,
            'temperature' => 39.2,
            'dressing_status' => 'Needs Change',
            'recorded_at' => Carbon::now()->subHours(2),
            'recorded_by_user_id' => $nurse->id,
        ]);

        Alert::firstOrCreate(
            ['patient_id' => $patient2->id, 'message' => 'High fever detected: 39.2 C'],
            [
                'indicator_type' => 'temperature',
                'value' => '39.2',
                'status' => AlertStatus::Sent,
                'created_at' => Carbon::now()->subHours(2),
            ]
        );

        Alert::firstOrCreate(
            ['patient_id' => $patient1->id, 'message' => 'Alerte : Température élevée'],
            [
                'indicator_type' => 'temperature',
                'value' => '38.2',
                'status' => AlertStatus::Sent,
                'created_at' => Carbon::now()->subHours(1),
            ]
        );

        Message::firstOrCreate(
            [
                'sender_id' => $patientUser1->id,
                'receiver_id' => $doctor->id,
                'content' => 'Bonjour Docteur, j’ai une légère fièvre ce matin (38,2°C). Le pansement est un peu rougeâtre.',
            ],
            ['read_status' => false, 'created_at' => Carbon::now()->subHours(3)]
        );

        Message::firstOrCreate(
            [
                'sender_id' => $doctor->id,
                'receiver_id' => $patientUser1->id,
                'content' => 'Jean, merci pour le message. Prenez du paracétamol et surveillez. Si la température dépasse 38,5°C ou si la plaie gonfle, venez aux urgences.',
            ],
            ['read_status' => true, 'created_at' => Carbon::now()->subHours(2)]
        );

        Message::firstOrCreate(
            [
                'sender_id' => $doctor->id,
                'receiver_id' => $patientUser2->id,
                'content' => 'Marie, votre dernier bilan est bon. Continuez la rééducation comme prévu.',
            ],
            ['read_status' => false, 'created_at' => Carbon::now()->subDay()]
        );

        Report::firstOrCreate(
            [
                'patient_id' => $patient1->id,
                'report_type' => 'Bilan post-opératoire J+21',
            ],
            [
                'generated_by' => $doctor->id,
                'content' => "Patient Jean Martin - Suivi appendicectomie.\nCicatrisation correcte. Douleur résiduelle faible. Dernière température 38,2°C à surveiller.\nRecommandation : contrôle sous 48h.",
                'created_at' => Carbon::now()->subDay(),
            ]
        );

        Report::firstOrCreate(
            [
                'patient_id' => $patient2->id,
                'report_type' => 'Suivi arthroscopie',
            ],
            [
                'generated_by' => $doctor->id,
                'content' => "Patient Marie Bernard - Arthroscopie genou.\nRécupération dans la norme. Une alerte fièvre récente (39,2°C) - à surveiller.",
                'created_at' => Carbon::now()->subDays(2),
            ]
        );
    }
}
