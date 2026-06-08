<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Verrouille le mot de passe du compte admin principal.
     * Objectif: le mot de passe stocké en base ne doit pas changer (seeders, updates, etc.).
     */
    public function updating(User $user): void
    {
        // On verrouille uniquement le compte "admin" principal (username).
        if ((string) $user->getOriginal('username') !== 'admin') {
            return;
        }

        if ($user->isDirty('password')) {
            $user->password = $user->getOriginal('password');
        }
    }
}

