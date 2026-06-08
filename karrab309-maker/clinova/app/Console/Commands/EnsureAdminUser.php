<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class EnsureAdminUser extends Command
{
    protected $signature = 'users:ensure-admin
                            {--username=admin : Nom d\'utilisateur admin}
                            {--email=admin@local.test : Email admin}
                            {--password= : Mot de passe (sinon CLINOVA_DEFAULT_PASSWORD / Clinova@123456)}';

    protected $description = 'Crée (ou met à jour) un compte admin de démarrage.';

    public function handle(): int
    {
        $username = (string) $this->option('username');
        $email = (string) $this->option('email');
        $plain = (string) ($this->option('password') ?: env('CLINOVA_DEFAULT_PASSWORD', 'password123'));

        $user = User::where('username', $username)->orWhere('email', $email)->first();

        if (! $user) {
            // Mot de passe en clair : le modèle User applique le cast « hashed » à l’enregistrement.
            $user = User::create([
                'name' => 'Admin',
                'username' => $username,
                'email' => $email,
                'password' => $plain,
                'role' => 'Admin',
            ]);

            $this->info("Admin créé: username={$user->username} email={$user->email}");
            $this->warn("Mot de passe: {$plain}");
            return self::SUCCESS;
        }

        $user->forceFill([
            'role' => 'Admin',
            'username' => $username,
            'email' => $email,
            'name' => $user->name ?: 'Admin',
        ]);

        if (! empty($plain)) {
            $user->forceFill(['password' => $plain]);
        }

        $user->save();

        $this->info("Admin mis à jour: id={$user->id} username={$user->username} email={$user->email}");
        $this->warn("Mot de passe: {$plain}");
        return self::SUCCESS;
    }
}

