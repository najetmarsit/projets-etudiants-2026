<?php

namespace App\Http\Controllers;

use App\Models\NursingNote;
use App\Models\Patient;
use App\Services\BillingItemService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NursingNoteController extends Controller
{
    public function index(Request $request, string $patientId)
    {
        $user = Auth::user();
        $patient = Patient::find($patientId);
        if (! $patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found'], 404);
        }

        $canRead = $user->isAdmin()
            || ($user->isDoctor() && (int) $patient->assigned_doctor_id === (int) $user->id)
            || ($user->isNurse()); // Infirmier : accès à tous les patients (lecture)

        if (! $canRead) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $q = NursingNote::query()
            ->with(['nurse:id,name,username'])
            ->where('patient_id', (int) $patient->id)
            ->orderByDesc('created_at');

        return response()->json(['success' => true, 'data' => $q->get()]);
    }

    public function store(Request $request, string $patientId, NotificationService $notifications)
    {
        $user = Auth::user();
        if (! $user->isNurse()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $patient = Patient::find($patientId);
        if (! $patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found'], 404);
        }
        // Infirmier : accès à tous les patients (écriture observations)

        $validator = Validator::make($request->all(), [
            'note' => 'required|string|max:5000',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $note = NursingNote::create([
            'patient_id' => (int) $patient->id,
            'nurse_user_id' => (int) $user->id,
            'note' => (string) $request->note,
        ]);

        // Facturation automatique : passage infirmier / visite.
        app(BillingItemService::class)->createAutoPriced(
            (int) $patient->id,
            'visit',
            'Visite infirmier • ' . mb_strimwidth((string) $request->note, 0, 80, '…'),
            (int) $user->id,
            $note->created_at ? \Carbon\Carbon::parse($note->created_at) : now(),
            NursingNote::class,
            (int) $note->id
        );

        $base = [
            'patient_id' => $patient->id,
            'channel' => 'staff_web',
            'type' => 'nurse.note_created',
            'title' => 'Nouvelle observation infirmière',
            'body' => (string) $request->note,
            'priority' => 'normal',
            'data' => [
                'patient_id' => $patient->id,
                'nursing_note_id' => $note->id,
                'nurse_user_id' => $user->id,
            ],
            'created_by_user_id' => $user->id,
            'recipient_user_id' => null,
        ];

        foreach (['admin', 'doctor', 'nurse'] as $audience) {
            $notifications->broadcastToAudience(array_merge($base, ['audience' => $audience]));
        }

        return response()->json([
            'success' => true,
            'message' => 'Observation enregistrée',
            'data' => $note->load(['nurse:id,name,username']),
        ], 201);
    }

    /**
     * Signalement urgent par l'infirmier vers médecin/admin (notifications staff web).
     */
    public function signalUrgent(Request $request, string $patientId, NotificationService $notifications)
    {
        $user = Auth::user();
        if (! $user->isNurse()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $patient = Patient::with(['user', 'assignedDoctor', 'assignedNurse'])->find($patientId);
        if (! $patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found'], 404);
        }
        // Infirmier : accès à tous les patients (signalement urgent)
        // Compat : anciennes apps envoyaient `note` au lieu de `message`.
        if (! $request->filled('message') && $request->filled('note')) {
            $request->merge(['message' => (string) $request->input('note')]);
        }

        $validated = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
            'priority' => 'sometimes|string|in:normal,urgent',
        ])->validate();

        $title = 'Signalement urgent (infirmier)';
        $body = $validated['message'];
        $priority = $validated['priority'] ?? 'urgent';

        $base = [
            'patient_id' => $patient->id,
            'channel' => 'staff_web',
            'type' => 'nurse.urgent_signal',
            'title' => $title,
            'body' => $body,
            'priority' => $priority,
            'data' => [
                'patient_id' => $patient->id,
                'room_number' => $patient->room_number,
                'bed_number' => $patient->bed_number,
                'nurse_user_id' => $user->id,
                'priority' => $priority,
            ],
            'created_by_user_id' => $user->id,
        ];

        // Admin (broadcast)
        $notifications->broadcastToAudience(array_merge($base, [
            'audience' => 'admin',
            'recipient_user_id' => null,
        ]));

        // Médecin assigné si existant, sinon broadcast doctor
        if ($patient->assignedDoctor) {
            $notifications->notifyUser($patient->assignedDoctor, array_merge($base, [
                'audience' => 'doctor',
            ]));
        } else {
            $notifications->broadcastToAudience(array_merge($base, [
                'audience' => 'doctor',
                'recipient_user_id' => null,
            ]));
        }

        $notifications->broadcastToAudience(array_merge($base, [
            'audience' => 'nurse',
            'recipient_user_id' => null,
        ]));

        return response()->json(['success' => true, 'message' => 'Signalement envoyé.']);
    }
}

