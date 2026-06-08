<?php

namespace App\Http\Controllers;

use App\Models\LabDocument;
use App\Models\Patient;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LabDocumentController extends Controller
{
    /**
     * Liste des documents : patient = les siens ; laboratoire = ceux qu'il a envoyés ;
     * admin / médecin = tous (filtrables par patient_id).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $q = LabDocument::query()->with(['patient.user', 'uploader:id,name,username']);

        if ($user->isPatient()) {
            $patient = Patient::where('user_id', $user->id)->first();
            if (!$patient) {
                return response()->json(['success' => true, 'data' => []]);
            }
            $q->where('patient_id', $patient->id);
        } elseif ($user->isLaboratory()) {
            $q->where('uploaded_by', $user->id);
        } elseif ($user->isAdmin() || $user->isDoctor()) {
            if ($request->filled('patient_id')) {
                $q->where('patient_id', (int) $request->get('patient_id'));
            }
        } else {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = $q->orderByDesc('created_at')->get();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request, NotificationService $notifications)
    {
        $user = Auth::user();
        if (!$user->isLaboratory()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf|max:15360',
            'notify_to' => 'sometimes|string|in:patient,doctor',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('file');
        $patientId = (int) $request->input('patient_id');
        $dir = 'lab_documents/'.$patientId;
        $path = $file->store($dir, 'public');

        $doc = LabDocument::create([
            'patient_id' => $patientId,
            'uploaded_by' => $user->id,
            'title' => $request->input('title'),
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/pdf',
        ]);

        $notifyTo = strtolower((string) $request->input('notify_to', 'patient'));
        if (! in_array($notifyTo, ['patient', 'doctor'], true)) {
            $notifyTo = 'patient';
        }

        $patient = Patient::find($patientId);
        $titleNotify = 'Nouvelle analyse laboratoire';
        $bodyNotify = (string) $doc->title;

        if ($notifyTo === 'doctor' && $patient) {
            $base = [
                'patient_id' => $patient->id,
                'channel' => 'staff_web',
                'type' => 'lab.document_uploaded',
                'title' => $titleNotify,
                'body' => $bodyNotify,
                'priority' => 'normal',
                'data' => [
                    'lab_document_id' => $doc->id,
                    'patient_id' => $patient->id,
                ],
                'created_by_user_id' => $user->id,
            ];
            $doctorUserId = $patient->assigned_doctor_id ? (int) $patient->assigned_doctor_id : null;
            if ($doctorUserId) {
                $doctor = User::find($doctorUserId);
                if ($doctor) {
                    $notifications->notifyUser($doctor, array_merge($base, ['audience' => 'doctor']));
                } else {
                    $notifications->broadcastToAudience(array_merge($base, [
                        'audience' => 'doctor',
                        'recipient_user_id' => null,
                    ]));
                }
            } else {
                $notifications->broadcastToAudience(array_merge($base, [
                    'audience' => 'doctor',
                    'recipient_user_id' => null,
                ]));
            }
        } elseif ($patient) {
            $notifications->notifyPatient($patient, [
                'type' => 'lab.document_uploaded',
                'title' => $titleNotify,
                'body' => $bodyNotify,
                'priority' => 'normal',
                'data' => [
                    'lab_document_id' => $doc->id,
                    'patient_id' => $patient->id,
                ],
                'created_by_user_id' => $user->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Document enregistré',
            'data' => $doc->load(['patient.user', 'uploader:id,name,username']),
        ], 201);
    }

    /**
     * Téléchargement sécurisé (JWT).
     */
    public function download(string $id): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        $doc = LabDocument::with('patient')->find($id);

        if (!$doc) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        if (!$this->canAccessDocument($user, $doc)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!Storage::disk('public')->exists($doc->stored_path)) {
            return response()->json(['success' => false, 'message' => 'File missing'], 404);
        }

        return Storage::disk('public')->download(
            $doc->stored_path,
            $doc->original_filename,
            ['Content-Type' => $doc->mime_type]
        );
    }

    private function canAccessDocument($user, LabDocument $doc): bool
    {
        if ($user->isAdmin() || $user->isDoctor()) {
            return true;
        }
        if ($user->isLaboratory()) {
            return (int) $doc->uploaded_by === (int) $user->id;
        }
        if ($user->isPatient()) {
            $patient = Patient::where('user_id', $user->id)->first();

            return $patient && (int) $doc->patient_id === (int) $patient->id;
        }

        return false;
    }
}
