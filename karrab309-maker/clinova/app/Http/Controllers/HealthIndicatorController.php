<?php

namespace App\Http\Controllers;

use App\Models\HealthIndicator;
use App\Models\Patient;
use App\Services\AlertService;
use App\Services\BillingItemService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HealthIndicatorController extends Controller
{
    public function __construct(protected AlertService $alertService) {}

    /**
     * Liste des indicateurs (staff : filtre patient_id ; patient : les siens, lecture seule).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->isAdmin() || $user->isDoctor() || $user->isNurse()) {
            $query = HealthIndicator::with(['patient.user', 'recordedBy:id,name,username']);

            if ($request->has('patient_id')) {
                $query->where('patient_id', $request->patient_id);
            }

            $indicators = $query->orderBy('recorded_at', 'desc')->get();
        } else {
            $patient = Patient::where('user_id', $user->id)->first();
            if ($patient) {
                $indicators = HealthIndicator::where('patient_id', $patient->id)
                    ->with(['patient.user', 'recordedBy:id,name,username'])
                    ->orderBy('recorded_at', 'desc')
                    ->get();
            } else {
                $indicators = [];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $indicators,
        ]);
    }

    /**
     * Création des constantes vitales : réservée aux infirmiers.
     */
    public function store(Request $request, NotificationService $notifications)
    {
        $user = Auth::user();

        if (! $user->isNurse()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'heart_rate' => 'required|integer|min:30|max:220',
            'temperature' => 'required|numeric|min:30|max:45',
            'blood_glucose' => 'required|numeric|min:1|max:35',
            'blood_pressure_systolic' => 'required|integer|min:60|max:250',
            'blood_pressure_diastolic' => 'required|integer|min:30|max:150',
            'recorded_at' => 'nullable|date',
        ]);

        $validator->after(function ($validator) use ($request) {
            $sys = (int) $request->input('blood_pressure_systolic', 0);
            $dia = (int) $request->input('blood_pressure_diastolic', 0);
            if ($sys > 0 && $dia > 0 && $sys <= $dia) {
                $validator->errors()->add('blood_pressure_systolic', 'La systolique doit être supérieure à la diastolique.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $patientId = (int) $request->patient_id;

        $indicator = HealthIndicator::create([
            'patient_id' => $patientId,
            'heart_rate' => (int) $request->heart_rate,
            'temperature' => $request->temperature,
            'blood_glucose' => $request->blood_glucose,
            'blood_pressure_systolic' => (int) $request->blood_pressure_systolic,
            'blood_pressure_diastolic' => (int) $request->blood_pressure_diastolic,
            'pain_level' => 0,
            'dressing_status' => 'Good',
            'recorded_at' => $request->recorded_at ?? now(),
            'recorded_by_user_id' => $user->id,
        ]);

        $label = sprintf(
            'Constantes infirmier • FC %d bpm • %.1f °C • %.1f mmol/L • TA %d/%d',
            $indicator->heart_rate,
            (float) $indicator->temperature,
            (float) $indicator->blood_glucose,
            $indicator->blood_pressure_systolic,
            $indicator->blood_pressure_diastolic
        );
        app(BillingItemService::class)->createAutoPriced(
            $patientId,
            'visit',
            $label,
            $user->id,
            $indicator->recorded_at ? \Carbon\Carbon::parse($indicator->recorded_at) : now(),
            HealthIndicator::class,
            (int) $indicator->id
        );

        $base = [
            'patient_id' => $patientId,
            'channel' => 'staff_web',
            'type' => 'patient.health_indicator_recorded',
            'title' => 'Constantes enregistrées',
            'body' => sprintf(
                'FC %d bpm • Temp. %s °C • Glycémie %s mmol/L • TA %d/%d',
                $indicator->heart_rate,
                $indicator->temperature,
                $indicator->blood_glucose,
                $indicator->blood_pressure_systolic,
                $indicator->blood_pressure_diastolic
            ),
            'priority' => 'normal',
            'data' => [
                'patient_id' => $patientId,
                'health_indicator_id' => $indicator->id,
            ],
            'created_by_user_id' => $user->id,
            'recipient_user_id' => null,
        ];
        foreach (['admin', 'doctor', 'nurse'] as $audience) {
            $notifications->broadcastToAudience(array_merge($base, ['audience' => $audience]));
        }

        $alert = $this->alertService->checkAndCreateAlert($indicator);

        $response = [
            'success' => true,
            'message' => 'Health indicator recorded successfully',
            'data' => $indicator->load(['patient.user', 'recordedBy:id,name,username']),
        ];

        if ($alert) {
            $alertBase = [
                'patient_id' => $patientId,
                'channel' => 'staff_web',
                'type' => 'patient.alert_created',
                'title' => 'Alerte patient',
                'body' => $alert->message,
                'priority' => $alert->priority ?? 'urgent',
                'data' => [
                    'patient_id' => $patientId,
                    'alert_id' => $alert->id,
                    'indicator_type' => $alert->indicator_type,
                    'value' => $alert->value,
                ],
                'created_by_user_id' => $user->id,
                'recipient_user_id' => null,
            ];
            foreach (['admin', 'doctor', 'nurse'] as $audience) {
                $notifications->broadcastToAudience(array_merge($alertBase, ['audience' => $audience]));
            }

            $response['alert'] = $alert;
            $response['message'] .= ' Alert generated due to abnormal readings.';
        }

        return response()->json($response, 201);
    }

    public function show(string $id)
    {
        $user = Auth::user();
        $indicator = HealthIndicator::with(['patient.user', 'recordedBy:id,name,username'])->find($id);

        if (! $indicator) {
            return response()->json([
                'success' => false,
                'message' => 'Health indicator not found',
            ], 404);
        }

        if (
            ! $user->isAdmin()
            && ! $user->isDoctor()
            && ! $user->isNurse()
            && $indicator->patient->user_id !== $user->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $indicator,
        ]);
    }

    /**
     * Mise à jour : réservée aux infirmiers (même périmètre que la saisie).
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        if (! $user->isNurse()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $indicator = HealthIndicator::find($id);

        if (! $indicator) {
            return response()->json([
                'success' => false,
                'message' => 'Health indicator not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'heart_rate' => 'required|integer|min:30|max:220',
            'temperature' => 'required|numeric|min:30|max:45',
            'blood_glucose' => 'required|numeric|min:1|max:35',
            'blood_pressure_systolic' => 'required|integer|min:60|max:250',
            'blood_pressure_diastolic' => 'required|integer|min:30|max:150',
            'recorded_at' => 'nullable|date',
        ]);

        $validator->after(function ($validator) use ($request) {
            $sys = (int) $request->input('blood_pressure_systolic', 0);
            $dia = (int) $request->input('blood_pressure_diastolic', 0);
            if ($sys > 0 && $dia > 0 && $sys <= $dia) {
                $validator->errors()->add('blood_pressure_systolic', 'La systolique doit être supérieure à la diastolique.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $indicator->update([
            'heart_rate' => (int) $request->heart_rate,
            'temperature' => $request->temperature,
            'blood_glucose' => $request->blood_glucose,
            'blood_pressure_systolic' => (int) $request->blood_pressure_systolic,
            'blood_pressure_diastolic' => (int) $request->blood_pressure_diastolic,
            'recorded_at' => $request->recorded_at ?? $indicator->recorded_at,
            'recorded_by_user_id' => $user->id,
        ]);

        $alert = $this->alertService->checkAndCreateAlert($indicator);

        $response = [
            'success' => true,
            'message' => 'Health indicator updated successfully',
            'data' => $indicator->load(['patient.user', 'recordedBy:id,name,username']),
        ];

        if ($alert) {
            $response['alert'] = $alert;
            $response['message'] .= ' Alert generated due to abnormal readings.';
        }

        return response()->json($response);
    }

    public function destroy(string $id)
    {
        $user = Auth::user();

        if (! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $indicator = HealthIndicator::find($id);

        if (! $indicator) {
            return response()->json([
                'success' => false,
                'message' => 'Health indicator not found',
            ], 404);
        }

        $indicator->delete();

        return response()->json([
            'success' => true,
            'message' => 'Health indicator deleted successfully',
        ]);
    }
}
