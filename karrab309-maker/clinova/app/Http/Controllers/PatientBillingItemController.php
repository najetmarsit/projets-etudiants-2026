<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Services\BillingItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PatientBillingItemController extends Controller
{
    /**
     * Ajout d'un acte facturable (nurse/doctor/admin).
     * Sert notamment pour médicaments / repas / analyses si non couverts par d'autres modules.
     */
    public function store(Request $request, string $patientId, BillingItemService $billing)
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdmin() && ! $user->isDoctor() && ! $user->isNurse())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $patient = Patient::find($patientId);
        if (! $patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'kind' => 'required|string|in:visit,medication,analysis,meal',
            'label' => 'required|string|max:255',
            'performed_at' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $item = $billing->createAutoPriced(
            (int) $patient->id,
            (string) $request->kind,
            (string) $request->label,
            (int) $user->id,
            $request->filled('performed_at') ? \Illuminate\Support\Carbon::parse((string) $request->performed_at) : now(),
            'manual',
            null
        );

        return response()->json(['success' => true, 'data' => $item], 201);
    }
}

