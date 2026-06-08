<?php

namespace App\Http\Controllers;

use App\Models\DoctorAvailability;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoctorAvailabilityController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        if (! $user || ! $user->isDoctor()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $row = DoctorAvailability::firstOrCreate(
            ['doctor_id' => $user->id],
            ['status' => 'available', 'last_seen_at' => now()]
        );

        $row->forceFill(['last_seen_at' => now()])->save();

        return response()->json(['success' => true, 'data' => $row]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        if (! $user || ! $user->isDoctor()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'status' => 'required|string|in:available,busy,offline,on_call',
        ]);

        $row = DoctorAvailability::firstOrCreate(
            ['doctor_id' => $user->id],
            ['status' => 'available', 'last_seen_at' => now()]
        );

        $row->forceFill([
            'status' => $data['status'],
            'last_seen_at' => now(),
        ])->save();

        // Broadcast temps réel (web Réception) via SSE notifications.
        // NB: on ne broadcast PAS sur show() (heartbeat), uniquement sur update() (changement explicite).
        $payload = [
            'patient_id' => null,
            'channel' => 'staff_web',
            'type' => 'doctor.availability.updated',
            'title' => 'Disponibilité médecin mise à jour',
            'body' => $user->name . ' est maintenant ' . $data['status'],
            'priority' => 'normal',
            'data' => [
                'doctor_id' => (int) $user->id,
                'status' => $data['status'],
                'last_seen_at' => optional($row->last_seen_at)->toIso8601String(),
            ],
            'created_by_user_id' => $user->id,
            'recipient_user_id' => null,
        ];
        foreach (['secretary', 'admin'] as $audience) {
            app(NotificationService::class)->broadcastToAudience(array_merge($payload, ['audience' => $audience]));
        }

        return response()->json(['success' => true, 'data' => $row]);
    }
}

