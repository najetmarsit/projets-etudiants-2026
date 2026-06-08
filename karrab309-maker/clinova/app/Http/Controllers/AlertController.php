<?php

namespace App\Http\Controllers;

use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\Patient;
use App\Services\AlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlertController extends Controller
{
    protected $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    /**
     * Display a listing of alerts.
     * Doctors and admins see alerts for their patients, patients see their own alerts.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $statusFilter = $request->has('status') ? (string) $request->status : null;
        // Compat rétro: le front peut encore envoyer "new"
        if ($statusFilter === 'new') {
            $statusFilter = AlertStatus::Sent->value;
        }

        if ($user->isAdmin()) {
            $query = Alert::with(['patient.user', 'assignedDoctor:id,name,username']);

            if ($request->has('patient_id')) {
                $query->where('patient_id', $request->patient_id);
            }
            if ($statusFilter !== null) {
                $query->where('status', $statusFilter);
            }

            $alerts = $query->orderBy('created_at', 'desc')->get();
        } elseif ($user->isDoctor()) {
            $query = Alert::with(['patient.user', 'assignedDoctor:id,name,username'])
                ->where('assigned_doctor_id', $user->id)
                ->where('indicator_type', 'not like', 'lab_rdv_%');

            if ($request->has('patient_id')) {
                $query->where('patient_id', $request->patient_id);
            }
            if ($statusFilter !== null) {
                $query->where('status', $statusFilter);
            }

            $alerts = $query->orderBy('created_at', 'desc')->get();
        } elseif ($user->isLaboratory()) {
            $query = Alert::with(['patient.user'])
                ->where('indicator_type', 'like', 'lab_rdv_%');

            if ($statusFilter !== null) {
                $query->where('status', $statusFilter);
            }

            $alerts = $query->orderBy('created_at', 'desc')->get();
        } elseif ($user->isNurse() || $user->isAccountant() || $user->isSecretary()) {
            $query = Alert::with(['patient.user', 'assignedDoctor:id,name,username'])
                ->where('indicator_type', 'not like', 'lab_rdv_%');

            if ($request->has('patient_id')) {
                $query->where('patient_id', $request->patient_id);
            }
            if ($statusFilter !== null) {
                $query->where('status', $statusFilter);
            }

            $alerts = $query->orderBy('created_at', 'desc')->get();
        } elseif ($user->isPatient()) {
            $patient = Patient::where('user_id', $user->id)->first();
            if ($patient) {
                $alerts = Alert::where('patient_id', $patient->id)
                    ->with('patient.user')
                    ->orderBy('created_at', 'desc')
                    ->get();
            } else {
                $alerts = [];
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    /**
     * Display the specified alert.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $alert = Alert::with(['patient.user', 'assignedDoctor:id,name,username'])->find($id);

        if (!$alert) {
            return response()->json([
                'success' => false,
                'message' => 'Alert not found'
            ], 404);
        }

        if ($user->isAdmin()) {
            return response()->json([
                'success' => true,
                'data' => $alert,
            ]);
        }

        if ($user->isLaboratory()) {
            if (! str_starts_with((string) $alert->indicator_type, 'lab_rdv_')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $alert,
            ]);
        }

        if ($user->isDoctor()) {
            if ((int) $alert->assigned_doctor_id !== (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }
            if (str_starts_with((string) $alert->indicator_type, 'lab_rdv_')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $alert,
            ]);
        }

        if ($user->isNurse() || $user->isAccountant() || $user->isSecretary()) {
            if (str_starts_with((string) $alert->indicator_type, 'lab_rdv_')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $alert,
            ]);
        }

        $patient = Patient::where('user_id', $user->id)->first();
        if ($patient && (int) $alert->patient_id === (int) $patient->id) {
            return response()->json([
                'success' => true,
                'data' => $alert,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized',
        ], 403);
    }

    /**
     * Acknowledge an alert.
     */
    public function acknowledge(string $id)
    {
        $user = Auth::user();
        $alert = Alert::find($id);

        if (!$alert) {
            return response()->json([
                'success' => false,
                'message' => 'Alert not found'
            ], 404);
        }

        $canAck = $user->isAdmin()
            || ($user->isDoctor() && (int) $alert->assigned_doctor_id === (int) $user->id)
            || ($user->isNurse() && ! str_starts_with((string) $alert->indicator_type, 'lab_rdv_'))
            || ($user->isLaboratory() && str_starts_with((string) $alert->indicator_type, 'lab_rdv_'))
            || ($alert->patient->user_id === $user->id);

        if (! $canAck) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($this->alertService->acknowledgeAlert($id)) {
            return response()->json([
                'success' => true,
                'message' => 'Alert acknowledged successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to acknowledge alert'
        ], 500);
    }

    /**
     * Remove the specified alert.
     * Only admins can delete alerts.
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

        $alert = Alert::find($id);

        if (!$alert) {
            return response()->json([
                'success' => false,
                'message' => 'Alert not found'
            ], 404);
        }

        $alert->delete();

        return response()->json([
            'success' => true,
            'message' => 'Alert deleted successfully'
        ]);
    }
}
