<?php

namespace App\Services;

use App\Enums\AlertStatus;
use App\Mail\AlertUrgentMail;
use App\Models\Alert;
use App\Models\HealthIndicator;
use App\Models\Operation;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertService
{
    /**
     * Check if a health indicator should trigger an alert and create one if needed
     */
    public function checkAndCreateAlert(HealthIndicator $indicator): ?Alert
    {
        if (!$indicator->shouldTriggerAlert()) {
            return null;
        }

        $patient = Patient::find($indicator->patient_id);
        $assignedDoctorId = $patient?->assigned_doctor_id;
        if (! $assignedDoctorId) {
            // fallback: dernier médecin ayant une opération avec ce patient
            $assignedDoctorId = Operation::where('patient_id', $indicator->patient_id)
                ->orderByDesc('operation_date')
                ->value('doctor_id');
        }

        $priority = $this->isUrgent($indicator) ? 'urgent' : 'normal';

        $alert = Alert::create([
            'patient_id' => $indicator->patient_id,
            'assigned_doctor_id' => $assignedDoctorId ? (int) $assignedDoctorId : null,
            'indicator_type' => $this->getIndicatorType($indicator),
            'value' => $this->getIndicatorValue($indicator),
            'priority' => $priority,
            'message' => $indicator->getAlertMessage(),
            'status' => AlertStatus::Sent,
            'assigned_at' => $assignedDoctorId ? now() : null,
        ]);

        $patient = Patient::with(['user', 'assignedDoctor'])->find($indicator->patient_id);
        if ($patient) {
            $this->notifyStaffByEmail($alert, $patient);
        }

        return $alert;
    }

    private function isUrgent(HealthIndicator $indicator): bool
    {
        // Priorité urgent pour toutes les conditions actuelles de déclenchement
        // (on garde un point d’extension si des règles "normal" apparaissent ensuite).
        return $indicator->shouldTriggerAlert();
    }

    /**
     * Get the type of indicator that triggered the alert
     */
    private function getIndicatorType(HealthIndicator $indicator): string
    {
        if ($indicator->pain_level > 7) {
            return 'pain_level';
        }

        if ($indicator->temperature > 38 || $indicator->temperature < 36.0) {
            return 'temperature';
        }

        if ($indicator->dressing_status === 'Infected') {
            return 'dressing_status';
        }

        if ($indicator->heart_rate !== null && ($indicator->heart_rate > 120 || $indicator->heart_rate < 45)) {
            return 'heart_rate';
        }

        if ($indicator->blood_glucose !== null && ($indicator->blood_glucose > 11.1 || $indicator->blood_glucose < 3.0)) {
            return 'blood_glucose';
        }

        if ($indicator->blood_pressure_systolic !== null && $indicator->blood_pressure_diastolic !== null) {
            if ($indicator->blood_pressure_systolic > 180 || $indicator->blood_pressure_diastolic > 110) {
                return 'blood_pressure';
            }
            if ($indicator->blood_pressure_systolic < 90) {
                return 'blood_pressure';
            }
        }

        return 'unknown';
    }

    /**
     * Get the value that triggered the alert
     */
    private function getIndicatorValue(HealthIndicator $indicator): string
    {
        if ($indicator->pain_level > 7) {
            return (string) $indicator->pain_level;
        }

        if ($indicator->temperature > 38 || $indicator->temperature < 36.0) {
            return (string) $indicator->temperature;
        }

        if ($indicator->dressing_status === 'Infected') {
            return $indicator->dressing_status;
        }

        if ($indicator->heart_rate !== null && ($indicator->heart_rate > 120 || $indicator->heart_rate < 45)) {
            return (string) $indicator->heart_rate;
        }

        if ($indicator->blood_glucose !== null && ($indicator->blood_glucose > 11.1 || $indicator->blood_glucose < 3.0)) {
            return (string) $indicator->blood_glucose;
        }

        if ($indicator->blood_pressure_systolic !== null && $indicator->blood_pressure_diastolic !== null) {
            if ($indicator->blood_pressure_systolic > 180 || $indicator->blood_pressure_diastolic > 110
                || $indicator->blood_pressure_systolic < 90) {
                return $indicator->blood_pressure_systolic.'/'.$indicator->blood_pressure_diastolic;
            }
        }

        return '';
    }

    /**
     * Get all active alerts for a patient
     */
    public function getActiveAlertsForPatient(int $patientId): \Illuminate\Database\Eloquent\Collection
    {
        return Alert::where('patient_id', $patientId)
            ->whereIn('status', AlertStatus::activeValues())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Acknowledge an alert
     */
    public function acknowledgeAlert(int $alertId): bool
    {
        $alert = Alert::find($alertId);
        if (!$alert) {
            return false;
        }

        $alert->acknowledge();
        return true;
    }

    private function notifyStaffByEmail(Alert $alert, Patient $patient): void
    {
        $emails = collect();

        foreach (User::where('role', 'Admin')->get() as $admin) {
            if ($admin->email) {
                $emails->push($admin->email);
            }
        }

        // notifier le médecin assigné en priorité, sinon fallback dernier médecin d'opération
        $doctorId = $patient->assigned_doctor_id
            ?: Operation::where('patient_id', $patient->id)->orderByDesc('operation_date')->value('doctor_id');

        if ($doctorId) {
            $doc = User::find($doctorId);
            if ($doc?->email) {
                $emails->push($doc->email);
            }
        }

        foreach ($emails->unique()->filter() as $email) {
            try {
                Mail::to($email)->send(new AlertUrgentMail($alert, $patient));
            } catch (\Throwable $e) {
                Log::warning('alert.email_failed', ['email' => $email, 'error' => $e->getMessage()]);
            }
        }
    }
}
