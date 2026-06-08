<?php

namespace App\Http\Controllers;

use App\Models\LabDocument;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Consultation du résumé dossier via jeton QR (sans authentification JWT).
 */
class PublicPatientDossierController extends Controller
{
    public function show(Request $request, string $token)
    {
        $patient = Patient::with([
            'user',
            'operations.doctor',
            'healthIndicators' => fn ($q) => $q->orderByDesc('recorded_at')->limit(20),
            'alerts',
            'labDocuments' => fn ($q) => $q->orderByDesc('created_at')->limit(30),
        ])
            ->where('qr_public_token', $token)
            ->first();

        if (! $patient) {
            return response()->json([
                'success' => false,
                'message' => 'Lien invalide ou expiré.',
            ], 404);
        }

        $ops = $patient->operations->map(fn ($op) => [
            'operation_type' => $op->operation_type,
            'operation_date' => $op->operation_date?->toIso8601String(),
            'notes' => $op->notes,
            'doctor_name' => $op->doctor?->name,
        ]);

        $activeAlerts = $patient->alerts->where('status', 'new')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'patient' => [
                    'name' => $patient->user?->name,
                    'age' => $patient->age,
                    'gender' => $patient->gender,
                    'medical_history' => $patient->medical_history,
                    'diagnosis' => $patient->diagnosis,
                    'prescribed_treatment' => $patient->prescribed_treatment,
                    'admission_at' => $patient->admission_at?->toIso8601String(),
                    'discharge_at' => $patient->discharge_at?->toIso8601String(),
                ],
                'appointments' => $ops,
                'recent_indicators' => $patient->healthIndicators->map(fn ($h) => [
                    'heart_rate' => $h->heart_rate,
                    'blood_glucose' => $h->blood_glucose,
                    'blood_pressure_systolic' => $h->blood_pressure_systolic,
                    'blood_pressure_diastolic' => $h->blood_pressure_diastolic,
                    'pain_level' => $h->pain_level,
                    'temperature' => $h->temperature,
                    'dressing_status' => $h->dressing_status,
                    'recorded_at' => $h->recorded_at?->format('Y-m-d H:i:s'),
                ]),
                'lab_documents' => $patient->labDocuments->map(fn ($d) => [
                    'id' => $d->id,
                    'title' => $d->title,
                    'original_filename' => $d->original_filename,
                    'mime_type' => $d->mime_type,
                    'created_at' => $d->created_at?->toIso8601String(),
                    'download_url' => url("/api/public/patient-dossier/{$token}/lab-documents/{$d->id}/download"),
                ]),
                'active_alerts_count' => $activeAlerts,
            ],
        ]);
    }

    /**
     * Téléchargement public d'une analyse PDF via token QR (sans JWT).
     * Vérifie que le document appartient bien au patient du token.
     */
    public function downloadLabDocument(Request $request, string $token, string $id): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        $patient = Patient::query()
            ->select(['id', 'qr_public_token'])
            ->where('qr_public_token', $token)
            ->first();

        if (! $patient) {
            return response()->json([
                'success' => false,
                'message' => 'Lien invalide ou expiré.',
            ], 404);
        }

        $doc = LabDocument::query()
            ->where('id', $id)
            ->where('patient_id', $patient->id)
            ->first();

        if (! $doc) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        if (! Storage::disk('public')->exists($doc->stored_path)) {
            return response()->json(['success' => false, 'message' => 'File missing'], 404);
        }

        return Storage::disk('public')->download(
            $doc->stored_path,
            $doc->original_filename,
            ['Content-Type' => $doc->mime_type ?: 'application/pdf']
        );
    }
}
