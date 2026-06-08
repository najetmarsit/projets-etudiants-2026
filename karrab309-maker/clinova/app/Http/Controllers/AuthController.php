<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use App\Services\ApiCacheService;
use App\Services\ProfileImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * Inscription publique désactivée : seuls les administrateurs créent les comptes (POST /api/admin/users).
     */
    public function register(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'La création de compte est réservée à l\'administrateur. Connectez-vous en tant qu\'admin pour créer des utilisateurs.',
        ], 403);
    }

    /**
     * Login user and return JWT token
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $login = trim((string) $request->input('username'));
            $password = (string) $request->input('password');
            $loginLower = mb_strtolower($login);

            // Permettre la connexion via username OU email dans le champ "username" (UI: Identifiant)
            $attempts = [];
            if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
                $attempts[] = ['email' => $login, 'password' => $password];
                if ($loginLower !== $login) {
                    $attempts[] = ['email' => $loginLower, 'password' => $password];
                }
            } else {
                $attempts[] = ['username' => $login, 'password' => $password];
                // Windows / UI : éviter les erreurs de casse (Admin vs admin)
                if ($loginLower !== $login) {
                    $attempts[] = ['username' => $loginLower, 'password' => $password];
                }
                $attempts[] = ['email' => $login, 'password' => $password];
                if ($loginLower !== $login) {
                    $attempts[] = ['email' => $loginLower, 'password' => $password];
                }
            }

            $token = null;
            foreach ($attempts as $creds) {
                $token = JWTAuth::attempt($creds);
                if ($token) {
                    break;
                }
            }

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiant ou mot de passe incorrect'
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create token'
            ], 500);
        }

        $user = Auth::user();

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Logout user (invalidate token)
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'success' => true,
                'message' => 'User logged out successfully'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout'
            ], 500);
        }
    }

    /**
     * Get authenticated user details
     */
    public function me(ApiCacheService $apiCache)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $ttl = (int) config('optimization.cache.auth_me_ttl', 90);
        $payload = $apiCache->remember(
            "auth:me:{$user->id}",
            fn () => ['success' => true, 'user' => $user->fresh()],
            $ttl
        );

        return response()->json($payload)
            ->header('Cache-Control', 'private, max-age='.$ttl)
            ->header('X-Cache', 'api');
    }

    /**
     * Patient connecté : récupérer son dossier patient (dont token QR public).
     */
    public function myPatient()
    {
        $user = Auth::user();
        if (! $user || ! $user->isPatient()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $patient = Patient::where('user_id', $user->id)->first();
        if (! $patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found'], 404);
        }

        if (empty($patient->qr_public_token)) {
            $patient->forceFill(['qr_public_token' => bin2hex(random_bytes(32))])->save();
        }

        return response()->json(['success' => true, 'data' => $patient->fresh()]);
    }

    /**
     * Update user locale (en, fr, ar). Syncs language between platform and mobile.
     */
    public function updateLocale(Request $request, ApiCacheService $apiCache)
    {
        $request->validate(['locale' => 'required|string|in:en,fr,ar']);

        $user = Auth::user();
        $user->update(['locale' => $request->locale]);
        $apiCache->forget("auth:me:{$user->id}");

        return response()->json([
            'success' => true,
            'locale' => $user->locale,
        ]);
    }

    /**
     * Refresh JWT token
     */
    public function refresh()
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            return response()->json([
                'success' => true,
                'token' => $token
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed'
            ], 401);
        }
    }

    /**
     * Upload profile photo (patient, doctor, admin). Visible par le médecin pour les patients.
     */
    public function uploadProfilePhoto(Request $request, ProfileImageOptimizer $optimizer, ApiCacheService $apiCache)
    {
        $maxKb = (int) config('optimization.upload.max_file_size', 5120);
        $request->validate([
            'photo' => 'required|file|image|mimes:jpeg,png,webp|max:' . $maxKb,
        ]);

        $user = Auth::user();
        $file = $request->file('photo');

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $optimizer->storeOptimized($file, (int) $user->id);
        $user->update(['profile_photo_path' => $path]);
        $apiCache->forget("auth:me:{$user->id}");

        return response()->json([
            'success' => true,
            'message' => 'Photo de profil enregistrée',
            'user' => $user->fresh(),
            'profile_photo_url' => Storage::disk('public')->url($path),
        ]);
    }
}
