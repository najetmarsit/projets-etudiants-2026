<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\SpecialistAppointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SpecialistAppointmentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $q = SpecialistAppointment::query()->with(['patient.user', 'creator:id,name,username'])->orderBy('scheduled_at');

        if ($user->isPatient()) {
            $patient = Patient::where('user_id', $user->id)->first();
            if (! $patient) {
                return response()->json(['success' => true, 'data' => []]);
            }
            $q->where('patient_id', $patient->id);
        } elseif ($user->isAdmin() || $user->isSecretary()) {
            if ($request->filled('patient_id')) {
                $q->where('patient_id', (int) $request->patient_id);
            }
            if ($request->filled('status')) {
                $q->where('status', (string) $request->status);
            }
        } elseif ($user->isDoctor()) {
            // Médecin: uniquement ses patients
            $patientIds = Patient::where('assigned_doctor_id', $user->id)->pluck('id')->toArray();
            $q->whereIn('patient_id', $patientIds);
            if ($request->filled('patient_id')) {
                $q->where('patient_id', (int) $request->patient_id);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json(['success' => true, 'data' => $q->get()]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (! $user->isAdmin() && ! $user->isSecretary()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'specialty' => 'required|string|max:120',
            'scheduled_at' => 'required|date',
            'note' => 'nullable|string|max:2000',
            'status' => 'sometimes|string|in:planned,confirmed,completed,cancelled',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $apt = SpecialistAppointment::create([
            'patient_id' => (int) $request->patient_id,
            'specialty' => (string) $request->specialty,
            'scheduled_at' => $request->scheduled_at,
            'status' => (string) ($request->status ?: 'planned'),
            'note' => $request->note,
            'created_by' => $user->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Rendez-vous créé', 'data' => $apt->load(['patient.user', 'creator:id,name,username'])], 201);
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        if (! $user->isAdmin() && ! $user->isSecretary()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $apt = SpecialistAppointment::find($id);
        if (! $apt) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'specialty' => 'sometimes|string|max:120',
            'scheduled_at' => 'sometimes|date',
            'note' => 'nullable|string|max:2000',
            'status' => 'sometimes|string|in:planned,confirmed,completed,cancelled',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $apt->update($request->only(['specialty', 'scheduled_at', 'note', 'status']));

        return response()->json(['success' => true, 'message' => 'Mis à jour', 'data' => $apt->fresh(['patient.user', 'creator:id,name,username'])]);
    }

    public function cancel(Request $request, string $id)
    {
        $user = Auth::user();
        $apt = SpecialistAppointment::find($id);
        if (! $apt) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        if ($user->isPatient()) {
            $patient = Patient::where('user_id', $user->id)->first();
            if (! $patient || (int) $apt->patient_id !== (int) $patient->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            if (! in_array($apt->status, ['planned', 'confirmed'], true)) {
                return response()->json(['success' => false, 'message' => 'Annulation impossible'], 422);
            }
        } elseif ($user->isAdmin() || $user->isSecretary()) {
            // ok
        } else {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $apt->update(['status' => 'cancelled']);
        return response()->json(['success' => true, 'message' => 'Annulé', 'data' => $apt->fresh()]);
    }
}

