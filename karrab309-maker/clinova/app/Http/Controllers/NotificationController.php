<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Patient;
use App\Services\ApiCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Inbox notifications.
     * - Staff (web): voit les notifications de son audience (+ ciblées à lui).
     * - Patient (mobile): voit uniquement ses notifications.
     */
    public function index(Request $request, ApiCacheService $apiCache)
    {
        $user = Auth::user();
        $perPage = min(max((int) $request->query('per_page', 30), 1), 100);
        $page = max((int) $request->query('page', 1), 1);
        $ver = $apiCache->notificationsVersion((int) $user->id);
        $cacheSuffix = md5(json_encode([
            'v' => $ver,
            'unread' => $request->query('unread'),
            'audience' => $request->query('audience'),
            'limit' => $request->query('limit'),
            'per_page' => $perPage,
            'page' => $page,
        ]));
        $ttl = (int) config('optimization.cache.notifications_ttl', 45);

        $payload = $apiCache->remember(
            "notifications:{$user->id}:{$cacheSuffix}",
            function () use ($request, $user, $perPage, $page) {
                return $this->buildNotificationsPayload($request, $user, $perPage, $page);
            },
            $ttl
        );

        return response()->json($payload)
            ->header('Cache-Control', 'private, max-age='.$ttl)
            ->header('X-Cache', 'api');
    }

    private function buildNotificationsPayload(Request $request, $user, int $perPage, int $page): array
    {
        $q = Notification::query()->orderByDesc('created_at');

        if ($user->isPatient()) {
            $patient = Patient::where('user_id', $user->id)->first();
            if (!$patient) {
                return ['success' => true, 'data' => [], 'meta' => ['page' => 1, 'per_page' => $perPage, 'count' => 0]];
            }
            $q->where('audience', 'patient')
              ->where('channel', 'patient_mobile')
              ->where('patient_id', $patient->id);
        } else {
            $audience = $this->audienceForUser($user);

            // Optionnel: admin peut forcer une audience via query (?audience=doctor)
            if ($user->isAdmin() && $request->filled('audience')) {
                $aud = strtolower((string) $request->query('audience'));
                if (in_array($aud, ['admin','doctor','nurse','laboratory','accountant','patient'], true)) {
                    $audience = $aud;
                }
            }

            $q->where('channel', 'staff_web')
              ->where(function ($sub) use ($audience, $user) {
                  $sub->where('audience', $audience)
                      ->whereNull('recipient_user_id')
                      ->orWhere('recipient_user_id', $user->id);
              });
        }

        if ($request->filled('unread')) {
            $unread = filter_var($request->query('unread'), FILTER_VALIDATE_BOOL);
            if ($unread) {
                $q->whereNull('read_at');
            }
        }

        if ($request->filled('limit')) {
            $limit = min((int) $request->query('limit', 50), 100);
            $items = $q->limit($limit)->get();
        } else {
            $items = $q->forPage($page, $perPage)->get();
        }

        return [
            'success' => true,
            'data' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'count' => $items->count(),
            ],
        ];
    }

    public function markRead(string $id, ApiCacheService $apiCache)
    {
        $n = Notification::find($id);
        if (!$n) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        // Sécurité minimale: on autorise seulement si le user peut voir cette notification
        if (! $this->canAccess(Auth::user(), $n)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (! $n->read_at) {
            $n->forceFill(['read_at' => now()])->save();
            $apiCache->forgetNotifications((int) Auth::id());
        }

        return response()->json(['success' => true, 'data' => $n->fresh()]);
    }

    public function acknowledge(string $id, ApiCacheService $apiCache)
    {
        $n = Notification::find($id);
        if (!$n) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        if (! $this->canAccess(Auth::user(), $n)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (! $n->acknowledged_at) {
            $n->forceFill(['acknowledged_at' => now()])->save();
            $apiCache->forgetNotifications((int) Auth::id());
        }

        return response()->json(['success' => true, 'data' => $n->fresh()]);
    }

    private function audienceForUser($user): string
    {
        if ($user->isAdmin()) return 'admin';
        if ($user->isDoctor()) return 'doctor';
        if (method_exists($user, 'isNurse') && $user->isNurse()) return 'nurse';
        if (method_exists($user, 'isSecretary') && $user->isSecretary()) return 'secretary';
        if ($user->isLaboratory()) return 'laboratory';
        if ($user->isAccountant()) return 'accountant';
        return 'admin';
    }

    private function canAccess($user, Notification $n): bool
    {
        if ($user->isPatient()) {
            $patient = Patient::where('user_id', $user->id)->first();
            return $n->audience === 'patient'
                && $n->channel === 'patient_mobile'
                && $patient
                && (int) $n->patient_id === (int) $patient->id;
        }

        if ($n->recipient_user_id && (int) $n->recipient_user_id === (int) $user->id) {
            return true;
        }

        return $n->channel === 'staff_web' && $n->audience === $this->audienceForUser($user);
    }
}

