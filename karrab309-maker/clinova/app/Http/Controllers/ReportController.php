<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Patient;
use App\Services\BillingItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    /**
     * List reports. Doctor/Admin: all or by patient_id; Patient: own only.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            $query = Report::with(['patient.user', 'generatedBy']);
            if ($request->has('patient_id')) {
                $query->where('patient_id', $request->patient_id);
            }
            $reports = $query->orderBy('created_at', 'desc')->get();
        } elseif ($user->isDoctor()) {
            $query = Report::with(['patient.user', 'generatedBy'])
                ->whereHas('patient', fn ($q) => $q->where('assigned_doctor_id', $user->id));
            if ($request->has('patient_id')) {
                $query->where('patient_id', $request->patient_id);
            }
            $reports = $query->orderBy('created_at', 'desc')->get();
        } else {
            $patient = Patient::where('user_id', $user->id)->first();
            $reports = $patient
                ? Report::where('patient_id', $patient->id)->with(['patient.user', 'generatedBy'])->orderBy('created_at', 'desc')->get()
                : collect();
        }

        return response()->json([
            'success' => true,
            'data' => $reports,
        ]);
    }

    /**
     * Generate and store a new report for a patient.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'report_type' => 'required|string|max:100',
            'content' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isDoctor()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $patient = Patient::with(['user', 'operations', 'healthIndicators', 'alerts'])->find($request->patient_id);
        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found'], 404);
        }

        if ($user->isDoctor() && (int) $patient->assigned_doctor_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez générer un rapport que pour un patient qui vous est assigné.',
            ], 403);
        }

        $content = $request->content;
        if (empty($content)) {
            $content = $this->generateReportContent($patient);
        }

        $report = Report::create([
            'patient_id' => $patient->id,
            'generated_by' => $user->id,
            'report_type' => $request->report_type,
            'content' => $content,
        ]);

        // Facturation automatique : acte médecin (rapport / visite).
        app(BillingItemService::class)->createAutoPriced(
            (int) $patient->id,
            'visit',
            'Visite médecin • Rapport: ' . (string) $request->report_type,
            (int) $user->id,
            $report->created_at ? \Carbon\Carbon::parse($report->created_at) : now(),
            Report::class,
            (int) $report->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Rapport généré',
            'data' => $report->load(['patient.user', 'generatedBy']),
        ], 201);
    }

    public function show(string $id)
    {
        $user = Auth::user();
        $report = Report::with(['patient.user', 'generatedBy'])->find($id);

        if (!$report) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }

        if ($user->isDoctor() && (int) $report->patient->assigned_doctor_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!$user->isAdmin() && !$user->isDoctor() && $report->patient->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    private function generateReportContent(Patient $patient): string
    {
        $lines = [];
        $lines[] = "Rapport de suivi - " . ($patient->user->name ?? 'Patient #' . $patient->id);
        $lines[] = "Âge: {$patient->age} ans | Genre: {$patient->gender}";
        $lines[] = "Antécédents: " . ($patient->medical_history ?? 'Aucun');
        $lines[] = '';
        $ops = $patient->operations;
        if ($ops->isNotEmpty()) {
            $lines[] = 'Opérations: '.$ops->pluck('operation_type')->implode(', ');
        }
        $indicators = $patient->healthIndicators->sortByDesc('recorded_at')->take(5);
        if ($indicators->isNotEmpty()) {
            $lines[] = "Derniers indicateurs: Douleur " . $indicators->avg('pain_level') . "/10, Température " . round($indicators->avg('temperature'), 1) . "°C";
        }
        $alerts = $patient->alerts->where('status', 'new');
        if ($alerts->isNotEmpty()) {
            $lines[] = "Alertes actives: " . $alerts->count();
        }
        $lines[] = "Généré le " . now()->format('d/m/Y H:i');
        return implode("\n", $lines);
    }
}
