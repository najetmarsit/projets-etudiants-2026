<?php

namespace App\Services;

use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\LabAppointment;
use App\Models\Patient;

class LabAppointmentNotificationService
{
    /** Notifie le personnel laboratoire (via liste d’alertes) : nouvelle demande de RDV. */
    public function notifyStaffNewRequest(LabAppointment $appointment): void
    {
        $patient = Patient::with('user')->find($appointment->patient_id);
        $name = $patient?->user?->name ?? ('Patient #'.$appointment->patient_id);
        $when = $appointment->scheduled_at->timezone(config('app.timezone'))->format('d/m/Y H:i');
        $note = $appointment->patient_note ? ' · '.$appointment->patient_note : '';

        Alert::create([
            'patient_id' => $appointment->patient_id,
            'assigned_doctor_id' => null,
            'indicator_type' => 'lab_rdv_request',
            'value' => (string) $appointment->id,
            'priority' => 'normal',
            'message' => "Nouveau RDV laboratoire : {$name} — le {$when}{$note}",
            'status' => AlertStatus::Sent,
        ]);
    }

    /** Notifie le patient : mise à jour du statut du RDV laboratoire. */
    public function notifyPatientStatusChanged(LabAppointment $appointment, ?string $previousStatus): void
    {
        if ($previousStatus !== null && $previousStatus === $appointment->status) {
            return;
        }

        $labels = [
            'pending' => 'en attente de confirmation',
            'confirmed' => 'confirmé',
            'completed' => 'terminé',
            'cancelled' => 'annulé',
        ];
        $txt = $labels[$appointment->status] ?? $appointment->status;
        $when = $appointment->scheduled_at->timezone(config('app.timezone'))->format('d/m/Y H:i');

        Alert::create([
            'patient_id' => $appointment->patient_id,
            'assigned_doctor_id' => null,
            'indicator_type' => 'lab_rdv_status',
            'value' => (string) $appointment->id,
            'priority' => 'normal',
            'message' => "Rendez-vous laboratoire ({$when}) : {$txt}.",
            'status' => AlertStatus::Sent,
        ]);
    }
}
