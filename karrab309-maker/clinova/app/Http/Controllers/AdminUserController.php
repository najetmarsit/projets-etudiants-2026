<?php

namespace App\Http\Controllers;

use App\Mail\NewUserCredentialsMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminUserController extends Controller
{
    /**
     * Création de compte par l’admin (tous les rôles métier). Option envoi identifiants par e-mail / trace SMS.
     */
    public function store(Request $request)
    {
        if (! Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($request->input('role') === 'Doctor') {
            $request->merge([
                'specialty' => trim((string) $request->input('specialty', '')),
            ]);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['nullable', 'string', 'min:8', 'regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/'],
            'role' => 'required|string|in:Admin,Doctor,Nurse,Secretary,Patient,Laboratory,Accountant',
            /**
             * Uniquement pour les médecins : spécialité obligatoire, 2–191 caractères.
             * exclude_unless : les autres rôles n’envoient pas / ne valident pas ce champ.
             */
            'specialty' => ['exclude_unless:role,Doctor', 'required', 'string', 'min:2', 'max:191'],
            'send_credentials' => 'sometimes|boolean',
            'phone' => 'nullable|string|max:50',
        ], [
            'password.regex' => 'Le mot de passe doit contenir au moins une lettre et un chiffre.',
            'specialty.required' => 'La spécialité est obligatoire pour un compte médecin.',
            'specialty.min' => 'La spécialité doit contenir au moins :min caractères.',
            'specialty.max' => 'La spécialité ne peut pas dépasser :max caractères.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $plainPassword = $request->filled('password')
            ? $request->password
            : Str::password(12, true, true, false);

        $specialty = $request->role === 'Doctor'
            ? trim((string) $request->input('specialty'))
            : null;

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => $plainPassword,
            'role' => $request->role,
            'specialty' => $specialty !== '' ? $specialty : null,
        ]);

        $send = $request->boolean('send_credentials');

        if ($send) {
            try {
                Mail::to($user->email)->send(new NewUserCredentialsMail($user, $plainPassword));
            } catch (\Throwable $e) {
                Log::warning('admin.create_user.mail_failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

                return response()->json([
                    'success' => false,
                    'message' => 'Compte créé mais l’envoi e-mail a échoué. Vérifiez la configuration mail (.env).',
                    'user' => $user,
                    'generated_password' => $plainPassword,
                ], 201);
            }

            if ($request->filled('phone')) {
                Log::info('sms.credentials.placeholder', [
                    'phone' => $request->phone,
                    'username' => $user->username,
                    'role' => $user->role,
                    'hint' => 'Branchez un fournisseur SMS (Twilio, etc.) pour envoi réel.',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé. Les identifiants ont été envoyés par e-mail.',
                'data' => $user,
            ], 201);
        }

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé. Transmettez le mot de passe par un canal sécurisé.',
            'data' => $user,
            'generated_password' => $plainPassword,
        ], 201);
    }
}
