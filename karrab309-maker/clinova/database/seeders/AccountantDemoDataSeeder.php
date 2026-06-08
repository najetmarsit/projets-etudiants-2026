<?php

namespace Database\Seeders;

use App\Models\InventoryMovement;
use App\Models\Patient;
use App\Models\PatientBillableItem;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Données d’exemple pour le portail comptable (Angular) :
 * entrées / sorties sur la période, trésorerie (encaissements + stock), file caisse (sorties avec reste dû).
 *
 * Idempotent : usernames et numéros de reçu fixes (updateOrCreate / firstOrCreate).
 */
class AccountantDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('CLINOVA_DEFAULT_PASSWORD', 'password123');
        $doctor = User::where('username', 'doctor')->first();
        $accountant = User::where('username', 'accountant')->first();
        $admin = User::where('username', 'admin')->first();
        $doctorId = $doctor?->id;
        $recorderId = $accountant?->id ?? $admin?->id;

        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();

        $labelPrefix = 'Démo compta — ';

        // --- File caisse : patient sorti avec solde restant (actes > paiements partiels) ---
        $uQueue1 = User::updateOrCreate(
            ['username' => 'clinova_demo_compta_queue1'],
            [
                'name' => 'Youssef Benjelloun',
                'email' => 'demo.compta.queue1@clinova.local',
                'password' => $password,
                'role' => 'Patient',
                'locale' => 'fr',
            ]
        );
        $pQueue1 = Patient::updateOrCreate(
            ['user_id' => $uQueue1->id],
            [
                'national_id' => 'CLINODEMOC1',
                'age' => 48,
                'gender' => 'Male',
                'phone' => '+212 6 00 11 22 33',
                'status' => 'outpatient',
                'assigned_doctor_id' => $doctorId,
                'admission_at' => $startOfMonth->copy()->addDays(4)->setTime(8, 30),
                'discharge_at' => $now->copy()->subDays(4)->setTime(11, 0),
                'medical_history' => 'Données de démonstration (comptabilité — file caisse).',
            ]
        );
        PatientBillableItem::firstOrCreate(
            ['patient_id' => $pQueue1->id, 'label' => $labelPrefix.'Forfait séjour'],
            [
                'kind' => 'visit',
                'amount' => 6200.00,
                'performed_at' => $pQueue1->discharge_at->copy()->subDay(),
                'created_by_user_id' => $doctorId,
            ]
        );
        PatientBillableItem::firstOrCreate(
            ['patient_id' => $pQueue1->id, 'label' => $labelPrefix.'Actes laboratoire'],
            [
                'kind' => 'analysis',
                'amount' => 1450.50,
                'performed_at' => $pQueue1->discharge_at->copy()->subDay(),
                'created_by_user_id' => $doctorId,
            ]
        );
        Payment::updateOrCreate(
            ['receipt_number' => 'clinova-seed-compta-partial-01'],
            [
                'patient_id' => $pQueue1->id,
                'recorded_by' => $recorderId,
                'payer_name' => 'Youssef Benjelloun',
                'total_amount' => 7650.50,
                'amount' => 4000.00,
                'currency' => 'MAD',
                'paid_at' => $now->copy()->subDays(3)->setTime(10, 15),
                'status' => 'paid',
                'provider' => 'manual',
            ]
        );
        Payment::updateOrCreate(
            ['receipt_number' => 'clinova-seed-compta-partial-02'],
            [
                'patient_id' => $pQueue1->id,
                'recorded_by' => $recorderId,
                'payer_name' => 'Youssef Benjelloun',
                'total_amount' => 7650.50,
                'amount' => 500.00,
                'currency' => 'MAD',
                'paid_at' => $now->copy()->subDays(10)->setTime(14, 0),
                'status' => 'paid',
                'provider' => 'manual',
            ]
        );

        // --- File caisse : sortie récente, rien payé ---
        $uQueue2 = User::updateOrCreate(
            ['username' => 'clinova_demo_compta_queue2'],
            [
                'name' => 'Salma El Ouazzani',
                'email' => 'demo.compta.queue2@clinova.local',
                'password' => $password,
                'role' => 'Patient',
                'locale' => 'fr',
            ]
        );
        $pQueue2 = Patient::updateOrCreate(
            ['user_id' => $uQueue2->id],
            [
                'national_id' => 'CLINODEMOC2',
                'age' => 35,
                'gender' => 'Female',
                'phone' => '+212 6 44 55 66 77',
                'status' => 'outpatient',
                'assigned_doctor_id' => $doctorId,
                'admission_at' => $startOfMonth->copy()->addDays(8)->setTime(7, 0),
                'discharge_at' => $now->copy()->subDay()->setTime(16, 30),
                'medical_history' => 'Données de démonstration (comptabilité — file caisse).',
            ]
        );
        PatientBillableItem::firstOrCreate(
            ['patient_id' => $pQueue2->id, 'label' => $labelPrefix.'Chirurgie ambulatoire'],
            [
                'kind' => 'visit',
                'amount' => 9800.00,
                'performed_at' => $pQueue2->discharge_at,
                'created_by_user_id' => $doctorId,
            ]
        );

        // --- Entrée du mois (toujours hospitalisé : compte dans « Entrées ») ---
        $uAdm1 = User::updateOrCreate(
            ['username' => 'clinova_demo_compta_admis1'],
            [
                'name' => 'Omar Kettani',
                'email' => 'demo.compta.admis1@clinova.local',
                'password' => $password,
                'role' => 'Patient',
                'locale' => 'fr',
            ]
        );
        Patient::updateOrCreate(
            ['user_id' => $uAdm1->id],
            [
                'national_id' => 'CLINODEMOA1',
                'age' => 61,
                'gender' => 'Male',
                'status' => 'hospitalized',
                'assigned_doctor_id' => $doctorId,
                'admission_at' => $startOfMonth->copy()->addDays(2)->setTime(9, 0),
                'discharge_at' => null,
                'room_number' => '210',
                'medical_history' => 'Données de démonstration (comptabilité — entrée mois en cours).',
            ]
        );

        // --- Admission + sortie dans le mois en cours, dossier soldé (sorties + encaissement) ---
        $uAdm2 = User::updateOrCreate(
            ['username' => 'clinova_demo_compta_admis2'],
            [
                'name' => 'Leila Hammani',
                'email' => 'demo.compta.admis2@clinova.local',
                'password' => $password,
                'role' => 'Patient',
                'locale' => 'fr',
            ]
        );
        $dischargeLeila = $startOfMonth->copy()->addDays(10);
        if ($dischargeLeila->greaterThan($now)) {
            $dischargeLeila = $now->copy()->subDay()->setTime(15, 0);
        }
        $admissionLeila = $startOfMonth->copy()->addDays(1)->setTime(10, 0);
        if ($admissionLeila->greaterThan($dischargeLeila)) {
            $admissionLeila = $dischargeLeila->copy()->subDays(5)->setTime(10, 0);
        }
        $pAdm2 = Patient::updateOrCreate(
            ['user_id' => $uAdm2->id],
            [
                'national_id' => 'CLINODEMOA2',
                'age' => 54,
                'gender' => 'Female',
                'status' => 'outpatient',
                'assigned_doctor_id' => $doctorId,
                'admission_at' => $admissionLeila,
                'discharge_at' => $dischargeLeila,
                'medical_history' => 'Données de démonstration (comptabilité — sortie soldée).',
            ]
        );
        PatientBillableItem::firstOrCreate(
            ['patient_id' => $pAdm2->id, 'label' => $labelPrefix.'Séjour soldé'],
            [
                'kind' => 'visit',
                'amount' => 2400.00,
                'performed_at' => $pAdm2->discharge_at,
                'created_by_user_id' => $doctorId,
            ]
        );
        Payment::updateOrCreate(
            ['receipt_number' => 'clinova-seed-compta-full-03'],
            [
                'patient_id' => $pAdm2->id,
                'recorded_by' => $recorderId,
                'payer_name' => 'Leila Hammani',
                'total_amount' => 2400.00,
                'amount' => 2400.00,
                'currency' => 'MAD',
                'paid_at' => $pAdm2->discharge_at->copy()->addHour(),
                'status' => 'paid',
                'provider' => 'manual',
            ]
        );

        // --- Mouvements stock (graphique trésorerie comptable) ---
        $dInv1 = $now->copy()->subDays(6)->format('Y-m-d');
        InventoryMovement::firstOrCreate(
            [
                'label' => $labelPrefix.'Réception consommables bloc',
                'movement_date' => $dInv1,
                'direction' => 'in',
            ],
            [
                'category' => 'consumable',
                'quantity' => 120,
                'unit' => 'unité',
                'total_value' => 18500.00,
                'currency' => 'MAD',
                'recorded_by' => $recorderId,
                'notes' => 'Seed démo comptable (achat stock).',
            ]
        );
        $dInv2 = $now->copy()->subDays(4)->format('Y-m-d');
        InventoryMovement::firstOrCreate(
            [
                'label' => $labelPrefix.'Consommation pansements / salle',
                'movement_date' => $dInv2,
                'direction' => 'out',
            ],
            [
                'category' => 'consumable',
                'quantity' => 35,
                'unit' => 'unité',
                'total_value' => 4200.00,
                'currency' => 'MAD',
                'recorded_by' => $recorderId,
                'notes' => 'Seed démo comptable (valeur consommée).',
            ]
        );
    }
}
