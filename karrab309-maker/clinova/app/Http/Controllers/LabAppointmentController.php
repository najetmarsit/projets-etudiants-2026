<?php

namespace App\Http\Controllers;

use App\Models\LabAppointment;
use App\Models\Patient;
use App\Services\LabAppointmentNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LabAppointmentController extends Controller
{
    public function __construct(
        private LabAppointmentNotificationService $labNotifications
    ) {
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = LabAppointment::with(['patient.user', 'confirmedByUser'])
            ->orderBy('scheduled_at');

        if ($user->isPatient()) {
            $patient = Patient::where('user_id', $user->id)->first();
            if (! $patient) {
                return response()->json(['success' => true, 'data' => []]);
            }
            $query->where('patient_id', $patient->id);
        } elseif ($user->isLaboratory() || $user->isAdmin()) {
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (! $user->isPatient() && ! $user->isAdmin() && ! $user->isSecretary()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $patient = null;
        if ($user->isPatient()) {
            $patient = Patient::where('user_id', $user->id)->first();
            if (! $patient) {
                return response()->json(['success' => false, 'message' => 'Patient not found'], 404);
            }
        }

        $validator = Validator::make($request->all(), [
            'scheduled_at' => 'required|date',
            'patient_id' => ($user->isPatient() ? 'nullable' : 'required') . '|exists:patients,id',
            'patient_note' => 'nullable|string|max:2000',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $patientId = $user->isPatient() ? $patient->id : (int) $request->patient_id;

        $appointment = LabAppointment::create([
            'patient_id' => $patientId,
            'scheduled_at' => $request->scheduled_at,
            'status' => 'pending',
            'patient_note' => $request->patient_note,
        ]);

        $this->labNotifications->notifyStaffNewRequest($appointment->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Rendez-vous demandé',
            'data' => $appointment->load(['patient.user']),
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        if (! $user->isLaboratory() && ! $user->isAdmin() && ! $user->isSecretary()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $appointment = LabAppointment::find($id);
        if (! $appointment) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string|in:pending,confirmed,completed,cancelled',
            'lab_note' => 'nullable|string|max:2000',
            'scheduled_at' => 'sometimes|date',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $previousStatus = $appointment->status;

        $data = $request->only(['status', 'lab_note', 'scheduled_at']);
        if ($request->filled('status') && in_array($request->status, ['confirmed', 'completed'], true)) {
            $data['confirmed_by'] = $user->id;
        }
        $appointment->update($data);
        $appointment->refresh();

        if ($user->isLaboratory() || $user->isAdmin()) {
            if ($request->filled('status') && $appointment->status !== $previousStatus) {
                $this->labNotifications->notifyPatientStatusChanged($appointment, $previousStatus);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Mis à jour',
            'data' => $appointment->fresh(['patient.user', 'confirmedByUser']),
        ]);
    }

    public function cancel(Request $request, string $id)
    {
        $user = Auth::user();
        $appointment = LabAppointment::find($id);
        if (! $appointment) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        if ($user->isPatient()) {
            $patient = Patient::where('user_id', $user->id)->first();
            if (! $patient || (int) $appointment->patient_id !== (int) $patient->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            if ($appointment->status !== 'pending') {
                return response()->json(['success' => false, 'message' => 'Annulation impossible'], 422);
            }
        } elseif ($user->isAdmin() || $user->isLaboratory() || $user->isSecretary()) {
            // ok
        } else {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $previousStatus = $appointment->status;
        $appointment->update(['status' => 'cancelled']);
        $appointment->refresh();
        if (! $user->isPatient()) {
            $this->labNotifications->notifyPatientStatusChanged($appointment, $previousStatus);
        }

        return response()->json(['success' => true, 'message' => 'Annulé', 'data' => $appointment->fresh()]);
    }
}
