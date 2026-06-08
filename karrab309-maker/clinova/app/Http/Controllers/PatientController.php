<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use App\Mail\PatientAdmissionCredentialsMail;
use App\Services\ApiCacheService;
use App\Services\CacheService;
use App\Services\NotificationService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    // Champs administratifs (admin + réception). NB: admission_at = "date d’entrée à la clinique".
    private const ADMIN_FIELDS = [
        'first_name',
        'last_name',
        'birth_date',
        'age',
        'gender',
        'phone',
        'address',
        'chamber_number',
        'admission_at',
        'national_id',
        'appointment_at',
        /** Motif / maladie saisi à la réception (le médecin peut compléter au suivi). */
        'current_illness',
    ];

    private const DOCTOR_FIELDS = [
        'medical_history',
        'diagnosis',
        'current_illness',
        'prescribed_treatment',
        'doctor_observations',
        'pre_op_report',
        'post_op_report',
    ];

    private const BILLING_FIELDS = [
        'admission_at',
        'discharge_at',
        'billing_notes',
        'billing_total_due',
        'billing_breakdown',
    ];

    /**
     * Display a listing of patients.
     */
    public function index(Request $request, ApiCacheService $apiCache)
    {
        $user = Auth::user();
        $perPage = min((int) $request->input('per_page', 50), 200);
        $cursor = (string) $request->input('cursor', '');
        $searchQ = trim((string) $request->input('q', ''));
        $ver = $apiCache->patientsIndexVersion((int) $user->id);
        $globalVer = $apiCache->patientsGlobalVersion();
        $listCacheKey = md5(json_encode([
            'v' => $ver,
            'gv' => $globalVer,
            'role' => $user->role,
            'per_page' => $perPage,
            'cursor' => $cursor,
            'q' => $searchQ,
        ]));
        $ttl = (int) config('optimization.cache.patient_list_ttl', 60);

        if ($user->isPatient()) {
            return $this->success($this->patientListForPatientUser($user));
        }

        $payload = $apiCache->remember(
            "patients:index:{$user->id}:{$listCacheKey}",
            function () use ($request, $user, $perPage, $cursor, $searchQ) {
                return $this->buildPatientsIndexPayload($request, $user, $perPage, $cursor, $searchQ);
            },
            $ttl
        );

        return response()->json($payload)
            ->header('Cache-Control', 'private, max-age='.$ttl)
            ->header('X-Cache', 'api');
    }

    private function patientListForPatientUser($user): array
    {
        $patient = Patient::where('user_id', $user->id)
            ->select([
                'id', 'user_id', 'assigned_doctor_id',
                'first_name', 'last_name', 'age', 'gender', 'phone', 'address',
                'medical_history', 'diagnosis', 'current_illness',
                'prescribed_treatment', 'doctor_observations',
                'pre_op_report', 'post_op_report',
                'status', 'room_number', 'bed_number', 'chamber_number',
                'admission_at', 'discharge_at', 'created_at', 'updated_at',
            ])
            ->with(['user:id,name,username,email,profile_photo_path,locale,role', 'assignedDoctor:id,name,username'])
            ->first();

        return $patient ? [$patient] : [];
    }

    /**
     * Filtre serveur (prénom, nom, téléphone, CIN) — indexé côté SQL quand possible.
     */
    private function applyPatientSearch(\Illuminate\Database\Eloquent\Builder $query, string $search): void
    {
        $term = trim($search);
        if ($term === '') {
            return;
        }

        $like = '%'.addcslashes($term, '%_\\').'%';
        $nid = Patient::normalizeNationalId($term);
        $likeNid = $nid !== '' ? '%'.addcslashes($nid, '%_\\').'%' : null;

        $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($like, $likeNid) {
            $q->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('phone', 'like', $like);
            if ($likeNid !== null) {
                $q->orWhere('national_id', 'like', $likeNid);
            }
        });
    }

    private function buildPatientsIndexPayload(Request $request, $user, int $perPage, string $cursor, string $searchQ = ''): array
    {
        if ($user->isAdmin() || $user->isLaboratory() || $user->isAccountant()) {
            $select = [
                'id', 'user_id', 'assigned_doctor_id', 'assigned_nurse_id',
                'first_name', 'last_name', 'age', 'gender', 'phone', 'address',
                'status', 'room_number', 'bed_number', 'chamber_number', 'national_id',
                'medical_history', 'diagnosis', 'current_illness', 'prescribed_treatment',
                'doctor_observations', 'pre_op_report', 'post_op_report',
                'admission_at', 'appointment_at', 'discharge_at', 'qr_public_token',
                'created_at', 'updated_at',
            ];
            $query = Patient::query()
                ->select($select)
                ->with(['user:id,name,username,email,profile_photo_path,locale,role', 'assignedDoctor:id,name,username']);
            $this->applyPatientSearch($query, $searchQ);
            $patients = $cursor !== ''
                ? $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor)
                : $query->cursorPaginate($perPage);
        } elseif ($user->isSecretary()) {
            $query = Patient::query()
                ->select([
                    'id', 'user_id', 'assigned_doctor_id', 'assigned_nurse_id',
                    'first_name', 'last_name', 'age', 'gender',
                    'phone', 'address', 'status', 'room_number', 'bed_number',
                    'chamber_number', 'national_id', 'current_illness',
                    'admission_at', 'appointment_at', 'discharge_at',
                    'created_at', 'updated_at',
                ])
                ->with([
                    'user:id,name,username,email,profile_photo_path,locale,role',
                    'assignedDoctor:id,name,username',
                ]);
            $this->applyPatientSearch($query, $searchQ);
            $patients = $cursor !== ''
                ? $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor)
                : $query->cursorPaginate($perPage);
        } elseif ($user->isDoctor()) {
            $query = Patient::query()
                ->select([
                    'id', 'user_id', 'assigned_doctor_id',
                    'first_name', 'last_name', 'age', 'gender', 'phone',
                    'status', 'room_number', 'bed_number', 'chamber_number',
                    'medical_history', 'diagnosis', 'current_illness',
                    'prescribed_treatment', 'doctor_observations',
                    'pre_op_report', 'post_op_report',
                    'admission_at', 'created_at', 'updated_at',
                ])
                ->with(['user:id,name,username,email,profile_photo_path,locale,role'])
                ->where('assigned_doctor_id', $user->id);
            $this->applyPatientSearch($query, $searchQ);
            $patients = $cursor !== ''
                ? $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor)
                : $query->cursorPaginate($perPage);
        } elseif (method_exists($user, 'isNurse') && $user->isNurse()) {
            $query = Patient::query()
                ->select([
                    'id', 'user_id', 'assigned_doctor_id', 'assigned_nurse_id',
                    'first_name', 'last_name', 'age', 'gender', 'phone',
                    'status', 'room_number', 'bed_number', 'chamber_number',
                    'current_illness', 'admission_at', 'created_at', 'updated_at',
                ])
                ->with(['user:id,name,username,email,profile_photo_path,locale,role', 'assignedDoctor:id,name,username']);
            $this->applyPatientSearch($query, $searchQ);
            $patients = $cursor !== ''
                ? $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor)
                : $query->cursorPaginate($perPage);
        } else {
            return [
                'success' => true,
                'data' => $this->patientListForPatientUser($user),
            ];
        }

        return [
            'success' => true,
            'data' => $patients->items(),
            'meta' => [
                'next_cursor' => $patients->nextCursor()?->encode(),
                'per_page' => $perPage,
                'has_more' => $patients->hasMorePages(),
            ],
        ];
    }

    /**
     * Comptes patients sans fiche (réservé à l'admin).
     */
    public function usersForAssignment()
    {
        $user = Auth::user();
        if (!$user->isAdmin() && ! $user->isSecretary()) {
            return $this->unauthorized();
        }

        $patientUserIds = Patient::pluck('user_id')->toArray();
        $users = User::where('role', 'Patient')
            ->whereNotIn('id', $patientUserIds)
            ->select('id', 'name', 'username', 'email')
            ->orderBy('name')
            ->get();

        return $this->success($users);
    }

    /**
     * Création fiche patient : coordonnées administratives — admin ou secrétaire (compte existant ou création compte patient / new_user).
     * Après création par une secrétaire : notification à l’audience admin pour assignation médecin / infirmière.
     */
    public function store(Request $request, NotificationService $notifications)
    {
        $user = Auth::user();

        if (!$user->isAdmin() && ! $user->isSecretary()) {
            return $this->unauthorized('Only Admin or Secretary can register patient records.');
        }

        $hasUserId = $request->filled('user_id') && (int) $request->user_id > 0;
        $newUser = $request->input('new_user');
        $hasNewUser = is_array($newUser) && ! empty(array_filter($newUser, fn ($v) => $v !== null && $v !== ''));

        // Compte mobile patient (new_user) : admin ou secrétaire (rôle Patient uniquement, créé ici).
        if ($hasUserId && $hasNewUser) {
            return $this->validationError([
                'user' => ['Indiquez soit un compte existant (user_id), soit new_user, pas les deux.'],
            ]);
        }

        if (! $hasUserId && ! $hasNewUser) {
            return $this->validationError([
                'user_id' => ['Sélectionnez un compte patient existant ou créez-en un (new_user).'],
            ]);
        }

        $request->merge([
            'national_id' => Patient::normalizeNationalId($request->input('national_id')),
        ]);

        $rules = [
            'user_id' => 'nullable|exists:users,id',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            // Date de naissance (nouveau champ) : l'âge est dérivé et conservé pour compatibilité.
            'birth_date' => 'required|date|before_or_equal:today',
            'age' => 'nullable|integer|min:0|max:150',
            'gender' => 'required|string|in:Male,Female,Other',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'chamber_number' => 'nullable|string|max:50',
            'admission_at' => 'nullable|date',
            'appointment_at' => 'nullable|date',
            'current_illness' => 'nullable|string|max:4000',
            'national_id' => ['required', 'string', 'regex:/^[A-Z0-9]{6,20}$/', Rule::unique('patients', 'national_id')],
        ];

        if ($hasNewUser) {
            $rules['new_user'] = 'required|array';
            $rules['new_user.name'] = 'required|string|max:255';
            $rules['new_user.email'] = 'required|string|email|max:255|unique:users,email';
            /** Le compte patient utilise le CIN comme identifiant unique (ignorer tout username envoyé). */
            $rules['new_user.password'] = ['nullable', 'string', 'min:8', 'regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/'];
            $rules['new_user.password_confirmation'] = 'nullable|string';
        }

        $validator = Validator::make($request->all(), $rules, [
            'new_user.password.regex' => 'Le mot de passe doit contenir au moins une lettre et un chiffre.',
            'national_id.regex' => 'Le CIN doit comporter entre 6 et 20 lettres ou chiffres (sans séparateurs).',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        // Calculer l'âge à partir de la date de naissance (compatibilité champs existants / front mobile).
        $birthDate = Carbon::parse((string) $request->input('birth_date'))->startOfDay();
        $computedAge = $birthDate->diffInYears(Carbon::now()->startOfDay());
        $request->merge(['age' => $computedAge]);

        if (
            $hasNewUser
            && $request->filled('new_user.password')
            && $request->input('new_user.password') !== $request->input('new_user.password_confirmation')
        ) {
            return $this->validationError([
                'new_user.password_confirmation' => ['Les mots de passe ne correspondent pas.'],
            ]);
        }

        if ($hasUserId) {
            if (Patient::where('user_id', $request->user_id)->exists()) {
                return $this->error('Patient record already exists for this user', 409);
            }
        }

        $nidForUsername = (string) $request->input('national_id');
        if ($hasNewUser && User::where('username', $nidForUsername)->exists()) {
            return $this->validationError([
                'national_id' => ['Ce CIN est déjà utilisé comme identifiant de connexion.'],
            ]);
        }

        if (
            $hasUserId
            && User::query()
                ->where('username', $nidForUsername)
                ->where('id', '!=', (int) $request->user_id)
                ->exists()
        ) {
            return $this->validationError([
                'national_id' => ['Ce CIN est déjà utilisé comme identifiant de connexion par un autre compte.'],
            ]);
        }

        $generatedPlainPassword = null;

        $patient = DB::transaction(function () use ($request, $hasNewUser, $hasUserId, &$generatedPlainPassword) {
            $userId = $hasUserId ? (int) $request->user_id : null;

            if ($hasNewUser) {
                $plainPassword = $request->filled('new_user.password')
                    ? (string) $request->input('new_user.password')
                    : Str::password(12, true, true, false);

                $username = (string) $request->input('national_id');

                $u = User::create([
                    'name' => $request->input('new_user.name'),
                    'username' => $username,
                    'email' => $request->input('new_user.email'),
                    'password' => Hash::make($plainPassword),
                    'role' => 'Patient',
                ]);
                $userId = $u->id;
                $generatedPlainPassword = $plainPassword;
            }

            $patient = Patient::create(array_merge(
                $request->only(self::ADMIN_FIELDS),
                ['user_id' => $userId, 'status' => 'admitted']
            ));

            $chamberRaw = $patient->chamber_number !== null ? trim((string) $patient->chamber_number) : '';
            if ($chamberRaw === '') {
                $patient->forceFill([
                    'chamber_number' => self::formatChamberNumber((int) $patient->id),
                ])->save();
            }

            if ($hasUserId) {
                self::syncPatientLoginUsernameFromNational($patient->fresh()->load('user'));
            }

            return $patient;
        });

        $patient->load('user');

        // Envoi des identifiants + n° chambre au patient (e-mail prioritaire, SMS en option).
        if ($hasNewUser && $patient->user) {
            try {
                Mail::to($patient->user->email)->send(new PatientAdmissionCredentialsMail(
                    user: $patient->user,
                    plainPassword: (string) $generatedPlainPassword,
                    chamberNumber: (string) $patient->chamber_number
                ));
            } catch (\Throwable $e) {
                Log::warning('patient.create.mail_failed', [
                    'patient_id' => $patient->id,
                    'user_id' => $patient->user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $phone = $patient->phone !== null ? trim((string) $patient->phone) : '';
            if ($phone !== '') {
                $smsBody = "Identifiants plateforme de suivi\n"
                    ."CIN / identifiant: {$patient->user->username}\n"
                    ."Mot de passe: {$generatedPlainPassword}\n"
                    ."Chambre: {$patient->chamber_number}";

                $sent = app(SmsService::class)->send($phone, $smsBody);

                if (! $sent) {
                    Log::info('sms.patient_credentials.placeholder', [
                        'phone' => $phone,
                        'username' => $patient->user->username,
                        'chamber_number' => $patient->chamber_number,
                        'hint' => 'Configurez TWILIO_ACCOUNT_SID / TWILIO_AUTH_TOKEN / TWILIO_FROM pour envoi réel.',
                    ]);
                }
            }
        }

        if ($user->isSecretary()) {
            $patientLabel = trim(implode(' ', array_filter([
                $patient->first_name,
                $patient->last_name,
            ])));
            if ($patientLabel === '') {
                $patientLabel = $patient->user?->name ?? ('Patient #'.$patient->id);
            }
            $phoneDisplay = $patient->phone !== null && trim((string) $patient->phone) !== ''
                ? trim((string) $patient->phone)
                : '—';
            $chamberRaw = $patient->chamber_number !== null ? trim((string) $patient->chamber_number) : '';
            $chamberDisplay = $chamberRaw !== '' ? $chamberRaw : '—';
            $nid = $patient->national_id !== null ? trim((string) $patient->national_id) : '';
            $illness = $patient->current_illness !== null ? trim((string) $patient->current_illness) : '';
            $illnessDisp = $illness !== '' ? $illness : '—';
            $rdv = $patient->appointment_at !== null
                ? Carbon::parse($patient->appointment_at)->toDateString()
                : null;
            $rdvDisp = $rdv ?? '—';

            $body = sprintf(
                "%s (%s) a enregistré les coordonnées administratives.\n\n· Patient : %s\n· CIN : %s\n· Maladie (accueil) : %s\n· Date RDV : %s\n· N° dossier : %d\n· Téléphone : %s\n· Chambre : %s\n\nAssignez un médecin et une infirmière depuis la fiche patient (espace admin).",
                $user->name,
                $user->username,
                $patientLabel,
                $nid !== '' ? $nid : '—',
                $illnessDisp,
                $rdvDisp,
                $patient->id,
                $phoneDisplay,
                $chamberDisplay
            );

            $notifications->broadcastToAudience([
                'patient_id' => $patient->id,
                'channel' => 'staff_web',
                'audience' => 'admin',
                'recipient_user_id' => null,
                'type' => 'secretary.patient_record_created',
                'title' => 'Nouveau patient — assignation à faire',
                'body' => $body,
                'priority' => 'normal',
                'data' => [
                    'patient_id' => $patient->id,
                    'patient_name' => $patientLabel,
                    'chamber_number' => $chamberRaw !== '' ? $chamberRaw : null,
                    'phone' => $patient->phone,
                    'secretary_user_id' => $user->id,
                    'kind' => 'secretary_new_patient',
                ],
                'created_by_user_id' => $user->id,
            ]);
        }

        $this->bustPatientCaches((int) $patient->id);

        return $this->created($patient, 'Patient created successfully');
    }

    private function generateUniqueUsername(string $displayName): string
    {
        $base = Str::of($displayName)->lower()->slug('')->toString();
        $base = preg_replace('/[^a-z0-9]/', '', $base ?? '') ?: 'patient';
        $base = substr($base, 0, 12);

        for ($i = 0; $i < 20; $i++) {
            $suffix = $i === 0 ? (string) random_int(100, 999) : (string) random_int(1000, 9999);
            $candidate = $base.$suffix;
            if (! User::where('username', $candidate)->exists()) {
                return $candidate;
            }
        }

        return 'patient'.Str::random(10);
    }

    private static function formatChamberNumber(int $patientId): string
    {
        return 'CH-'.str_pad((string) $patientId, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Connexion JWT + expérience mobile : username du compte patient = CIN normalisé du dossier lorsque sans conflit.
     */
    private static function syncPatientLoginUsernameFromNational(?Patient $patient): void
    {
        if (! $patient) {
            return;
        }

        $patient->loadMissing('user');

        $nid = $patient->national_id !== null ? trim((string) $patient->national_id) : '';
        if ($nid === '') {
            return;
        }

        $user = $patient->user;
        if (! $user || ! $user->isPatient()) {
            return;
        }

        if ($user->username === $nid) {
            return;
        }

        if (User::query()->where('username', $nid)->whereKeyNot($user->id)->exists()) {
            return;
        }

        $user->forceFill(['username' => $nid])->save();
    }

    /**
     * Admin : envoyer une notification urgente au médecin + infirmier (espaces dédiés).
     * Côté patient : une notification d'information est ajoutée dans l'app mobile.
     */
    public function urgentNotify(Request $request, string $id, NotificationService $notifications)
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            return $this->unauthorized();
        }

        $patient = Patient::with(['user', 'assignedDoctor', 'assignedNurse'])->find($id);
        if (! $patient) {
            return $this->notFound('Patient not found');
        }

        $validated = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
        ])->validate();

        $title = 'Alerte urgente : intervention requise';
        $body = $validated['message'];

        // Staff web: espace Médecin (doctor) et espace Infirmier (nurse)
        $base = [
            'patient_id' => $patient->id,
            'channel' => 'staff_web',
            'type' => 'patient.arrival_urgent',
            'title' => $title,
            'body' => $body,
            'priority' => 'urgent',
            'data' => [
                'patient_id' => $patient->id,
                'room_number' => $patient->room_number,
                'bed_number' => $patient->bed_number,
            ],
            'created_by_user_id' => $user->id,
        ];

        // Médecin: si un médecin est assigné → notification ciblée, sinon broadcast à tous les doctors
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

        // Infirmier: si un infirmier est assigné → notification ciblée, sinon broadcast à tous les nurses
        if ($patient->assignedNurse) {
            $notifications->notifyUser($patient->assignedNurse, array_merge($base, [
                'audience' => 'nurse',
            ]));
        } else {
            $notifications->broadcastToAudience(array_merge($base, [
                'audience' => 'nurse',
                'recipient_user_id' => null,
            ]));
        }

        // Patient mobile (info, pas "urgent staff")
        $notifications->notifyPatient($patient, [
            'type' => 'patient.care_started',
            'title' => 'Votre prise en charge commence',
            'body' => 'L’équipe médicale a été informée et interviendra dans les plus brefs délais.',
            'priority' => 'normal',
            'data' => [
                'patient_id' => $patient->id,
            ],
            'created_by_user_id' => $user->id,
        ]);

        return $this->success(null, 'Notifications envoyées.');
    }

    /**
     * Admin : orienter médecin(s) et/ou infirmier(s) vers un patient précis (notification staff web ciblée).
     * Corps enrichi : n° dossier, nom, chambre + message. Route legacy `notify-doctor` acceptée (médecin seul).
     */
    public function notifyStaffAboutPatient(Request $request, string $id, NotificationService $notifications)
    {
        $admin = Auth::user();
        if (! $admin || ! $admin->isAdmin()) {
            return $this->unauthorized();
        }

        $patient = Patient::with(['user', 'assignedDoctor:id,name,username', 'assignedNurse:id,name,username'])->find($id);
        if (! $patient) {
            return $this->notFound('Patient not found');
        }

        $validator = Validator::make($request->all(), [
            'doctor_user_id' => 'nullable|integer|exists:users,id',
            'nurse_user_ids' => 'nullable|array',
            'nurse_user_ids.*' => 'integer|exists:users,id',
            'nurse_user_id' => 'nullable|integer|exists:users,id',
            'message' => 'required|string|max:2000',
            'title' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $doctorId = $request->filled('doctor_user_id') ? (int) $request->doctor_user_id : null;
        $nurseIds = array_map('intval', (array) $request->input('nurse_user_ids', []));
        if ($request->filled('nurse_user_id')) {
            $nurseIds[] = (int) $request->nurse_user_id;
        }
        $nurseIds = array_values(array_unique(array_filter($nurseIds, fn ($v) => $v > 0)));

        if ($doctorId === null && $nurseIds === []) {
            return $this->validationError([
                'recipients' => ['Sélectionnez au moins un médecin ou une infirmière.'],
            ]);
        }

        $allUserIds = array_values(array_unique(
            $doctorId !== null ? array_merge([$doctorId], $nurseIds) : $nurseIds
        ));
        $users = User::whereIn('id', $allUserIds)->get()->keyBy('id');

        if ($doctorId !== null) {
            $doc = $users->get($doctorId);
            if (! $doc || ! $doc->isDoctor()) {
                return $this->validationError([
                    'doctor_user_id' => ['Le médecin sélectionné est invalide.'],
                ]);
            }
        }

        foreach ($nurseIds as $nid) {
            $nu = $users->get($nid);
            if (! $nu || ! $nu->isNurse()) {
                return $this->validationError([
                    'nurse_user_ids' => ['Une infirmière sélectionnée est invalide.'],
                ]);
            }
        }

        $patientLabel = trim(implode(' ', array_filter([
            $patient->first_name,
            $patient->last_name,
        ])));
        if ($patientLabel === '') {
            $patientLabel = $patient->user?->name ?? ('Patient #'.$patient->id);
        }

        $chamber = $patient->chamber_number !== null ? trim((string) $patient->chamber_number) : '';
        $chamberDisplay = $chamber !== '' ? $chamber : '—';

        $title = $request->filled('title')
            ? (string) $request->title
            : 'Orientation patient : '.$patientLabel.' (#'.$patient->id.')';

        $summary = 'Patient n°'.$patient->id.' — '.$patientLabel.' — Chambre : '.$chamberDisplay;
        $body = $summary."\n\n".trim((string) $request->message);

        $data = [
            'patient_id' => $patient->id,
            'patient_name' => $patientLabel,
            'chamber_number' => $chamber !== '' ? $chamber : null,
            'admin_user_id' => $admin->id,
            'kind' => 'admin_orient_staff',
        ];

        $base = [
            'patient_id' => $patient->id,
            'channel' => 'staff_web',
            'type' => 'admin.patient_orient_staff',
            'title' => $title,
            'body' => $body,
            'priority' => 'normal',
            'data' => $data,
            'created_by_user_id' => $admin->id,
        ];

        $sent = 0;
        if ($doctorId !== null) {
            $notifications->notifyUser($users->get($doctorId), array_merge($base, ['audience' => 'doctor']));
            $sent++;
        }
        foreach ($nurseIds as $nid) {
            $notifications->notifyUser($users->get($nid), array_merge($base, ['audience' => 'nurse']));
            $sent++;
        }

        return $this->success(null, $sent > 1 ? $sent.' notifications envoyées.' : 'Notification envoyée.');
    }

    /**
     * Display the specified patient.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        // Relations volumineuses : ordre récent + limite pour éviter N×lignes et payloads énormes (mobile / Angular).
        $patient = Patient::with([
            'user',
            'assignedDoctor:id,name,username',
            'assignedNurse:id,name,username',
            'operations' => function ($q) {
                $q->with(['doctor:id,name,username'])
                    ->orderByDesc('operation_date')
                    ->orderByDesc('id')
                    ->limit(150);
            },
            'healthIndicators' => function ($q) {
                $q->with(['recordedBy:id,name,username'])
                    ->orderByDesc('recorded_at')
                    ->orderByDesc('id')
                    ->limit(200);
            },
            'alerts' => function ($q) {
                $q->orderByDesc('created_at')->orderByDesc('id')->limit(150);
            },
            'reports' => function ($q) {
                $q->orderByDesc('id')->limit(120);
            },
            'labDocuments' => function ($q) {
                $q->with(['uploader:id,name,username'])
                    ->orderByDesc('id')
                    ->limit(100);
            },
        ])->find($id);

        if (!$patient) {
            return $this->notFound('Patient not found');
        }

        if ($user->isLaboratory()) {
            return $this->success($patient);
        }

        if ($user->isDoctor() && (int) $patient->assigned_doctor_id !== (int) $user->id) {
            return $this->unauthorized();
        }

        if (
            !$user->isAdmin()
            && !$user->isDoctor()
            && !$user->isAccountant()
            && !(method_exists($user, 'isNurse') && $user->isNurse())
            && !(method_exists($user, 'isSecretary') && $user->isSecretary())
            && $patient->user_id !== $user->id
        ) {
            return $this->unauthorized();
        }

        if ($user->isAdmin() || $user->isDoctor() || $user->isAccountant()
            || ($user->isPatient() && $patient->user_id === $user->id)) {
            $this->ensureQrToken($patient);
        }

        if ($user->isSecretary()) {
            $patient->makeHidden([
                'medical_history',
                'diagnosis',
                'prescribed_treatment',
                'doctor_observations',
                'pre_op_report',
                'post_op_report',
                'billing_notes',
                'billing_total_due',
                'billing_breakdown',
                'qr_public_token',
            ]);
            $patient->setRelation('healthIndicators', collect());
            $patient->setRelation('alerts', collect());
            $patient->setRelation('reports', collect());
            $patient->setRelation('labDocuments', collect());
            $patient->setRelation('operations', collect());
        }

        return $this->success($patient);
    }

    /**
     * Génère un jeton durable pour le QR code du dossier (consultation publique contrôlée).
     */
    private function ensureQrToken(Patient $patient): void
    {
        if (! empty($patient->qr_public_token)) {
            return;
        }
        $patient->forceFill(['qr_public_token' => bin2hex(random_bytes(32))])->save();
    }

    /**
     * Update : admin = coordonnées uniquement ; médecin = dossier médical / état clinique.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $patient = Patient::find($id);

        if (!$patient) {
            return $this->notFound('Patient not found');
        }

        if ($user->isAdmin() || $user->isSecretary()) {
            if ($request->filled('national_id')) {
                $request->merge([
                    'national_id' => Patient::normalizeNationalId($request->input('national_id')),
                ]);
            }

            $validator = Validator::make($request->all(), [
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'birth_date' => 'sometimes|date|before_or_equal:today',
                'age' => 'sometimes|integer|min:0|max:150',
                'gender' => 'sometimes|string|in:Male,Female,Other',
                'phone' => 'nullable|string|max:50',
                'address' => 'nullable|string',
                'room_number' => 'nullable|string|max:50',
                'bed_number' => 'nullable|string|max:50',
                'chamber_number' => 'nullable|string|max:50',
                'admission_at' => 'nullable|date',
                'appointment_at' => 'nullable|date',
                'current_illness' => 'nullable|string|max:4000',
                'national_id' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'regex:/^[A-Z0-9]{6,20}$/',
                    Rule::unique('patients', 'national_id')->ignore((int) $patient->id),
                ],
                'discharge_at' => 'nullable|date',
                'billing_notes' => 'nullable|string',
                'billing_total_due' => 'nullable|numeric|min:0',
                'billing_breakdown' => 'nullable|array',
                'billing_breakdown.*.label' => 'nullable|string|max:255',
                'billing_breakdown.*.amount' => 'nullable|numeric|min:0',
            ], [
                'national_id.regex' => 'Le CIN doit comporter entre 6 et 20 lettres ou chiffres (sans séparateurs).',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            if ($request->filled('birth_date')) {
                $birthDate = Carbon::parse((string) $request->input('birth_date'))->startOfDay();
                $computedAge = $birthDate->diffInYears(Carbon::now()->startOfDay());
                $request->merge(['age' => $computedAge]);
            }

            $allowed = array_merge(self::ADMIN_FIELDS, ['room_number', 'bed_number', 'chamber_number', 'discharge_at']);
            if ($user->isAdmin()) {
                $allowed = array_merge($allowed, self::BILLING_FIELDS);
            }

            $patient->update($request->only($allowed));

            if ($request->has('national_id')) {
                self::syncPatientLoginUsernameFromNational($patient->load('user'));
            }

            $base = [
                'patient_id' => (int) $patient->id,
                'channel' => 'staff_web',
                'type' => 'patient.updated',
                'title' => 'Dossier patient mis à jour',
                'body' => 'Mise à jour administrative (chambre/lit/admission/sortie).',
                'priority' => 'normal',
                'data' => [
                    'patient_id' => (int) $patient->id,
                    'updated_fields' => array_values(array_intersect(array_keys($request->all()), $allowed)),
                ],
                'created_by_user_id' => $user->id,
                'recipient_user_id' => null,
            ];
            foreach (['admin', 'doctor', 'nurse'] as $audience) {
                app(NotificationService::class)->broadcastToAudience(array_merge($base, ['audience' => $audience]));
            }

            $this->bustPatientCaches((int) $patient->id);

            return $this->success(
                $patient->load(['user', 'assignedDoctor:id,name,username']),
                'Patient updated successfully'
            );
        }

        if ($user->isDoctor()) {
            if ((int) $patient->assigned_doctor_id !== (int) $user->id) {
                return $this->unauthorized();
            }

            $validator = Validator::make($request->all(), [
                'medical_history' => 'nullable|string',
                'diagnosis' => 'nullable|string',
                'current_illness' => 'nullable|string',
                'prescribed_treatment' => 'nullable|string',
                'doctor_observations' => 'nullable|string',
                'pre_op_report' => 'nullable|string',
                'post_op_report' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $patient->update($request->only(self::DOCTOR_FIELDS));

            $base = [
                'patient_id' => (int) $patient->id,
                'channel' => 'staff_web',
                'type' => 'patient.updated',
                'title' => 'Dossier médical mis à jour',
                'body' => 'Mise à jour médicale (diagnostic / traitement / observations).',
                'priority' => 'normal',
                'data' => [
                    'patient_id' => (int) $patient->id,
                    'updated_fields' => array_values(array_intersect(array_keys($request->all()), self::DOCTOR_FIELDS)),
                ],
                'created_by_user_id' => $user->id,
                'recipient_user_id' => null,
            ];
            foreach (['admin', 'doctor', 'nurse'] as $audience) {
                app(NotificationService::class)->broadcastToAudience(array_merge($base, ['audience' => $audience]));
            }

            $this->bustPatientCaches((int) $patient->id);

            return $this->success($patient->load('user'), 'Patient updated successfully');
        }

        return $this->unauthorized('Only Admin (coordinates) or Doctor (medical record) can update.');
    }

    /**
     * Admin : assigner un médecin principal à un patient.
     */
    public function assignDoctor(Request $request, string $id)
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            return $this->unauthorized();
        }

        $patient = Patient::find($id);
        if (! $patient) {
            return $this->notFound('Patient not found');
        }

        $validator = Validator::make($request->all(), [
            'assigned_doctor_id' => 'nullable|exists:users,id',
        ]);
        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $doctorId = $request->input('assigned_doctor_id');
        if ($doctorId !== null) {
            $doc = User::find((int) $doctorId);
            if (! $doc || ! $doc->isDoctor()) {
                return $this->validationError([
                    'assigned_doctor_id' => ['Le médecin sélectionné est invalide.'],
                ]);
            }
        }

        $patient->forceFill([
            'assigned_doctor_id' => $doctorId ? (int) $doctorId : null,
            'assigned_at' => $doctorId ? now() : null,
        ])->save();

        $this->bustPatientCaches((int) $patient->id);

        return $this->success(
            $patient->load(['user', 'assignedDoctor:id,name,username']),
            'Assignment updated'
        );
    }

    /**
     * Remove the specified patient.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return $this->unauthorized();
        }

        $patient = Patient::find($id);

        if (!$patient) {
            return $this->notFound('Patient not found');
        }

        $patientId = (int) $patient->id;
        $patient->delete();

        $this->bustPatientCaches($patientId);

        return $this->success(null, 'Patient deleted successfully');
    }

    private function bustPatientCaches(int $patientId): void
    {
        $cache = app(CacheService::class);
        $cache->invalidatePatientCache($patientId);
        $cache->invalidateDashboardCache();
        app(ApiCacheService::class)->forgetPatientsIndex(Auth::id() ? (int) Auth::id() : null);
    }
}
