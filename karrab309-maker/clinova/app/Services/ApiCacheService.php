<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cache API lecture (GET) — clés par utilisateur, invalidation ciblée.
 */
class ApiCacheService
{
    private bool $enabled;

    public function __construct()
    {
        $this->enabled = (bool) config('optimization.cache.api_enabled', true);
    }

    public function remember(string $key, callable $callback, ?int $ttlSeconds = null): mixed
    {
        if (! $this->enabled) {
            return $callback();
        }

        $ttl = $ttlSeconds ?? (int) config('optimization.cache.api_ttl', 60);
        $fullKey = 'clinova:api:'.$key;

        try {
            return Cache::remember($fullKey, $ttl, $callback);
        } catch (\Throwable $e) {
            Log::warning('ApiCacheService: remember failed', ['key' => $fullKey, 'error' => $e->getMessage()]);

            return $callback();
        }
    }

    public function forget(string $key): void
    {
        try {
            Cache::forget('clinova:api:'.$key);
        } catch (\Throwable $e) {
            Log::warning('ApiCacheService: forget failed', ['key' => $key]);
        }
    }

    public function forgetUser(int $userId): void
    {
        $this->forget("auth:me:{$userId}");
        $this->forgetPattern("notifications:{$userId}:");
        $this->forgetPattern("messages:{$userId}:");
        $this->forgetPattern("patients:index:{$userId}:");
    }

    public function notificationsVersion(int $userId): int
    {
        return (int) Cache::get("clinova:notif_ver:{$userId}", 0);
    }

    public function forgetNotifications(int $userId): void
    {
        try {
            Cache::increment("clinova:notif_ver:{$userId}");
        } catch (\Throwable $e) {
            Cache::put("clinova:notif_ver:{$userId}", $this->notificationsVersion($userId) + 1, 86400);
        }
    }

    public function messagesVersion(int $userId): int
    {
        return (int) Cache::get("clinova:msg_ver:{$userId}", 0);
    }

    public function forgetMessages(int $userId): void
    {
        try {
            Cache::increment("clinova:msg_ver:{$userId}");
        } catch (\Throwable $e) {
            Cache::put("clinova:msg_ver:{$userId}", $this->messagesVersion($userId) + 1, 86400);
        }
    }

    public function patientsIndexVersion(int $userId): int
    {
        return (int) Cache::get("clinova:patients_ver:{$userId}", 0);
    }

    public function patientsGlobalVersion(): int
    {
        return (int) Cache::get('clinova:patients_global_ver', 0);
    }

    public function forgetPatientsIndex(?int $userId = null): void
    {
        try {
            Cache::increment('clinova:patients_global_ver');
        } catch (\Throwable $e) {
            Cache::put('clinova:patients_global_ver', $this->patientsGlobalVersion() + 1, 86400);
        }
        if ($userId !== null) {
            try {
                Cache::increment("clinova:patients_ver:{$userId}");
            } catch (\Throwable $e) {
                Cache::put("clinova:patients_ver:{$userId}", $this->patientsIndexVersion($userId) + 1, 86400);
            }
        }
    }

    public function keyForUser(string $segment, int $userId, string $suffix = ''): string
    {
        return $segment.':'.$userId.($suffix !== '' ? ':'.$suffix : '');
    }
}
