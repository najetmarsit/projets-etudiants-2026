<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Patient;
use App\Models\User;

class NotificationService
{
    /**
     * Crée une notification broadcast pour une audience (ex: doctor, nurse).
     */
    public function broadcastToAudience(array $payload): Notification
    {
        return Notification::create($payload);
    }

    /**
     * Crée une notification ciblée pour un utilisateur précis.
     */
    public function notifyUser(User $user, array $payload): Notification
    {
        $payload['recipient_user_id'] = $user->id;
        return Notification::create($payload);
    }

    /**
     * Notifie le patient (mobile) à partir d'un Patient.
     */
    public function notifyPatient(Patient $patient, array $payload): Notification
    {
        $payload['patient_id'] = $patient->id;
        $payload['audience'] = 'patient';
        $payload['channel'] = 'patient_mobile';
        $payload['recipient_user_id'] = null;
        return Notification::create($payload);
    }
}

