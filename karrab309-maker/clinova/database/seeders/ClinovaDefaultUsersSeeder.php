<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class ClinovaDefaultUsersSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('CLINOVA_DEFAULT_PASSWORD', 'password123');

        $defs = [
            ['role' => 'Admin', 'username' => 'admin', 'email' => 'admin@local.test', 'name' => 'Admin'],
            ['role' => 'Doctor', 'username' => 'doctor', 'email' => 'doctor@local.test', 'name' => 'Doctor', 'specialty' => 'Médecine générale'],
            ['role' => 'Nurse', 'username' => 'nurse', 'email' => 'nurse@local.test', 'name' => 'Nurse'],
            ['role' => 'Secretary', 'username' => 'secretary', 'email' => 'secretary@local.test', 'name' => 'Secretary'],
            ['role' => 'Accountant', 'username' => 'accountant', 'email' => 'accountant@local.test', 'name' => 'Accountant'],
            //['role' => 'Patient', 'username' => 'patient', 'email' => 'patient@local.test', 'name' => 'Patient'],
        ];

        foreach ($defs as $d) {
            // Mot de passe mis à jour à chaque seed : évite comptés « bloqués » si ancien hash corrompu.
            User::updateOrCreate(
                ['username' => $d['username']],
                [
                    'name' => $d['name'],
                    'email' => $d['email'],
                    'password' => $password,
                    'role' => $d['role'],
                    'specialty' => ($d['role'] === 'Doctor' && isset($d['specialty'])) ? $d['specialty'] : null,
                ]
            );
        }
    }
}

