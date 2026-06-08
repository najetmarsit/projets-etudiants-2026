<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    private int $defaultTtl;
    private bool $enabled;

    public function __construct()
    {
        $this->defaultTtl = (int) config('optimization.cache.dashboard_ttl', 300);
        $this->enabled = (bool) config('optimization.cache.dashboard_enabled', true);
    }

    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        if (!$this->enabled) {
            return $callback();
        }

        $ttl = $ttl ?? $this->defaultTtl;
        $cacheKey = $this->buildKey($key);

        try {
            return Cache::remember($cacheKey, $ttl, $callback);
        } catch (\Throwable $e) {
            Log::warning("CacheService: failed to remember {$cacheKey}", [
                'error' => $e->getMessage(),
            ]);
            return $callback();
        }
    }

    public function rememberForever(string $key, callable $callback): mixed
    {
        if (!$this->enabled) {
            return $callback();
        }

        $cacheKey = $this->buildKey($key);

        try {
            return Cache::rememberForever($cacheKey, $callback);
        } catch (\Throwable $e) {
            Log::warning("CacheService: failed to rememberForever {$cacheKey}", [
                'error' => $e->getMessage(),
            ]);
            return $callback();
        }
    }

    public function forget(string $key): void
    {
        Cache::forget($this->buildKey($key));
    }

    public function flush(array $tags = []): void
    {
        if (!empty($tags)) {
            Cache::tags($tags)->flush();
        } else {
            Cache::flush();
        }
    }

    private function buildKey(string $key): string
    {
        return 'clinova:' . $key;
    }

    public function getDashboardStatsKey(): string
    {
        return 'dashboard:stats';
    }

    public function getDashboardChartKey(int $days = 30): string
    {
        return "dashboard:chart:{$days}";
    }

    public function getPatientListKey(string $role = 'admin'): string
    {
        return "patients:list:{$role}";
    }

    public function getPatientDetailKey(int $patientId): string
    {
        return "patient:detail:{$patientId}";
    }

    public function getDoctorListKey(): string
    {
        return 'doctors:list';
    }

    public function getFinancialOverviewKey(string $from, string $to): string
    {
        $fromKey = str_replace([':', '-', ' '], '', $from);
        $toKey = str_replace([':', '-', ' '], '', $to);
        return "financial:overview:{$fromKey}:{$toKey}";
    }

    public function invalidatePatientCache(int $patientId): void
    {
        $this->forget($this->getDashboardStatsKey());
        $this->forget($this->getDashboardChartKey());
        $this->forget($this->getPatientDetailKey($patientId));
        $this->forget($this->getPatientListKey('admin'));
        $this->forget($this->getPatientListKey('doctor'));
        $this->forget($this->getDoctorListKey());
    }

    public function invalidateDashboardCache(): void
    {
        $this->forget($this->getDashboardStatsKey());
        $this->forget($this->getDashboardChartKey());
        $this->forget($this->getDoctorListKey());
        $this->forget('dashboard:analytics');
    }

    public function getDashboardAnalyticsKey(): string
    {
        return 'dashboard:analytics';
    }

    public function getDoctorAnalyticsKey(int $doctorId): string
    {
        return "dashboard:doctor:{$doctorId}";
    }

    public function getLabAnalyticsKey(): string
    {
        return 'dashboard:lab';
    }

    public function getSecretaryAnalyticsKey(): string
    {
        return 'dashboard:secretary';
    }

    public function invalidateRoleDashboardCaches(?int $doctorId = null): void
    {
        $this->invalidateDashboardCache();
        $this->forget($this->getLabAnalyticsKey());
        $this->forget($this->getSecretaryAnalyticsKey());
        if ($doctorId !== null) {
            $this->forget($this->getDoctorAnalyticsKey($doctorId));
        }
    }
}
