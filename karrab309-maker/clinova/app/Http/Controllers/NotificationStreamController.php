<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Patient;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * SSE "temps réel" sans WebSocket.
 *
 * Le client ouvre une connexion et reçoit les notifications au fur et à mesure.
 * - Staff : filtré par audience (+ ciblées recipient_user_id).
 * - Patient : uniquement ses notifications.
 */
class NotificationStreamController extends Controller
{
    public function stream(Request $request): StreamedResponse
    {
        // EventSource ne permet pas d'envoyer Authorization: Bearer ...
        // On accepte donc un JWT via query string (?token=...), ou via guard auth:api.
        $user = null;
        try {
            if ($request->bearerToken()) {
                $user = auth('api')->user();
            } elseif ($request->filled('token')) {
                $user = JWTAuth::setToken((string) $request->query('token'))->authenticate();
            }
        } catch (\Throwable $e) {
            $user = null;
        }

        if (! $user) {
            return response()->stream(function () {
                echo "event: error\n";
                echo "data: " . json_encode(['message' => 'Unauthenticated']) . "\n\n";
                if (function_exists('ob_flush')) @ob_flush();
                @flush();
            }, 401, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
            ]);
        }

        // Garder un stream court (évite timeouts proxy) : le client reconnecte.
        $maxSeconds = max(5, min(45, (int) $request->query('max_seconds', 25)));
        $sinceId = (int) $request->query('since_id', 0);

        $response = new StreamedResponse(function () use ($user, $maxSeconds, $sinceId) {
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', '0');
            @set_time_limit(0);

            $start = time();
            $lastId = $sinceId;

            while ((time() - $start) < $maxSeconds) {
                $items = $this->queryForUser($user, $lastId)->limit(50)->get();

                foreach ($items as $n) {
                    $lastId = max($lastId, (int) $n->id);
                    $payload = [
                        'id' => $n->id,
                        'type' => $n->type,
                        'title' => $n->title,
                        'body' => $n->body,
                        'priority' => $n->priority,
                        'patient_id' => $n->patient_id,
                        'data' => $n->data,
                        'created_at' => optional($n->created_at)->toIso8601String(),
                    ];

                    echo "id: {$n->id}\n";
                    echo "event: notification\n";
                    echo "data: " . json_encode($payload) . "\n\n";
                }

                // Heartbeat pour garder la connexion active
                echo "event: ping\n";
                echo "data: " . json_encode(['t' => date('c'), 'last_id' => $lastId]) . "\n\n";

                if (function_exists('ob_flush')) @ob_flush();
                @flush();

                usleep(800000); // 0.8s
            }

            echo "event: end\n";
            echo "data: " . json_encode(['last_id' => $lastId]) . "\n\n";
            if (function_exists('ob_flush')) @ob_flush();
            @flush();
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');

        return $response;
    }

    private function queryForUser($user, int $afterId)
    {
        $q = Notification::query()->orderBy('id')->where('id', '>', $afterId);

        if ($user->isPatient()) {
            $patient = Patient::where('user_id', $user->id)->first();
            if (! $patient) {
                return $q->whereRaw('1=0');
            }
            return $q->where('audience', 'patient')
                ->where('channel', 'patient_mobile')
                ->where('patient_id', $patient->id);
        }

        $audience = $this->audienceForUser($user);

        return $q->where('channel', 'staff_web')
            ->where(function ($sub) use ($audience, $user) {
                $sub->where(function ($s) use ($audience) {
                    $s->where('audience', $audience)->whereNull('recipient_user_id');
                })->orWhere('recipient_user_id', $user->id);
            });
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
}

