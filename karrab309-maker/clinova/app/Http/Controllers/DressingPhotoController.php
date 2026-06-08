<?php

namespace App\Http\Controllers;

use App\Models\HealthIndicator;
use App\Models\Patient;
use App\Services\AlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DressingPhotoController extends Controller
{
    protected $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    /**
     * Upload a dressing photo for a patient. Crée un indicateur avec image_path.
     * Réservé au personnel (médecin, admin, infirmier) — pas au patient.
     */
    public function store(Request $request, string $patientId)
    {
        $user = Auth::user();
        $patient = Patient::find($patientId);

        if (! $patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found'], 404);
        }

        if (! $user->isDoctor() && ! $user->isAdmin() && ! $user->isNurse()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'image' => 'required|file|image|max:10240', // 10MB
            'pain_level' => 'nullable|integer|min:0|max:10',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'dressing_status' => 'nullable|string|in:Good,Needs Change,Infected',
        ]);

        $file = $request->file('image');
        $path = $file->store('dressing/' . $patientId, 'public');

        $indicator = HealthIndicator::create([
            'patient_id' => $patient->id,
            'pain_level' => $request->input('pain_level', 0),
            'temperature' => $request->input('temperature', 37.0),
            'dressing_status' => $request->input('dressing_status', 'Good'),
            'recorded_at' => now(),
            'image_path' => $path,
            'recorded_by_user_id' => $user->id,
        ]);

        $this->alertService->checkAndCreateAlert($indicator);

        return response()->json([
            'success' => true,
            'message' => 'Photo enregistrée',
            'data' => $indicator->load('patient.user'),
            'image_url' => Storage::disk('public')->url($path),
        ], 201);
    }
}
