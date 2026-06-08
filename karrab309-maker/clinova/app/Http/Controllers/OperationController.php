<?php

namespace App\Http\Controllers;

use App\Models\Operation;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OperationController extends Controller
{
    /**
     * Display a listing of operations.
     * Doctors see operations they performed, patients see their own operations, admins see all.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            $operations = Operation::with(['patient.user', 'doctor'])->get();
        } elseif ($user->isDoctor()) {
            $operations = Operation::where('doctor_id', $user->id)
                ->with(['patient.user', 'doctor'])
                ->get();
        } elseif ($user->isNurse() || $user->isAccountant() || $user->isSecretary()) {
            // Infirmier / compta / réception : lecture de toutes les interventions (parcours soins / admin).
            $operations = Operation::with(['patient.user', 'doctor'])
                ->orderByDesc('operation_date')
                ->limit(500)
                ->get();
        } else {
            // Patient : ses opérations uniquement
            $patient = Patient::where('user_id', $user->id)->first();
            if ($patient) {
                $operations = Operation::where('patient_id', $patient->id)
                    ->with(['patient.user', 'doctor'])
                    ->get();
            } else {
                $operations = [];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $operations
        ]);
    }

    /**
     * Store a newly created operation.
     * Only doctors can create operations.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->isDoctor() && !$user->isAdmin() && ! $user->isSecretary()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $rules = [
            'patient_id' => 'required|exists:patients,id',
            // Réception/admin : doit choisir un médecin. Médecin : implicite (doctor_id = user).
            'doctor_id' => [
                Rule::requiredIf(fn () => ! $user->isDoctor()),
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'Doctor')),
            ],
            'operation_type' => 'required|string|max:255',
            'operation_date' => 'required|date',
            'notes' => 'nullable|string',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify the patient belongs to the doctor (if not admin)
        if (!$user->isAdmin()) {
            $patient = Patient::find($request->patient_id);
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient not found'
                ], 404);
            }
            // For now, allow any doctor to operate on any patient
            // In a real system, you might have doctor-patient assignments
        }

        $doctorId = $user->isDoctor() ? (int) $user->id : (int) $request->doctor_id;

        $operation = Operation::create([
            'patient_id' => $request->patient_id,
            'doctor_id' => $doctorId,
            'operation_type' => $request->operation_type,
            'operation_date' => $request->operation_date,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Operation created successfully',
            'data' => $operation->load(['patient.user', 'doctor'])
        ], 201);
    }

    /**
     * Display the specified operation.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $operation = Operation::with(['patient.user', 'doctor'])->find($id);

        if (!$operation) {
            return response()->json([
                'success' => false,
                'message' => 'Operation not found'
            ], 404);
        }

        // Lecture : admin, médecin, infirmier, réception, compta, ou patient concerné
        if (! $user->isAdmin()
            && ! $user->isDoctor()
            && ! $user->isNurse()
            && ! $user->isSecretary()
            && ! $user->isAccountant()
            && $operation->patient->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $operation
        ]);
    }

    /**
     * Update the specified operation.
     * Only the doctor who performed the operation or admin can update.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $operation = Operation::find($id);

        if (!$operation) {
            return response()->json([
                'success' => false,
                'message' => 'Operation not found'
            ], 404);
        }

        // Check permissions (admin / réception / médecin qui a l'opération)
        if (!$user->isAdmin() && ! $user->isSecretary() && $operation->doctor_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'operation_type' => 'sometimes|string|max:255',
            'operation_date' => 'sometimes|date',
            'notes' => 'nullable|string',
            // Admin / réception : peut corriger le médecin associé si besoin.
            'doctor_id' => [
                'sometimes',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'Doctor')),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $operation->update($request->only(['operation_type', 'operation_date', 'notes', 'doctor_id']));

        return response()->json([
            'success' => true,
            'message' => 'Operation updated successfully',
            'data' => $operation->load(['patient.user', 'doctor'])
        ]);
    }

    /**
     * Remove the specified operation.
     * Only admins can delete operations.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $operation = Operation::find($id);

        if (!$operation) {
            return response()->json([
                'success' => false,
                'message' => 'Operation not found'
            ], 404);
        }

        $operation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Operation deleted successfully'
        ]);
    }
}
