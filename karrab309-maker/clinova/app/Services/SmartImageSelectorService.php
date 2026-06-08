<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sélection d’images contextuelles pour l’UI Clinova.
 *
 * Règles de sécurité : aucune donnée patient (nom, ID, diagnostic) n’est envoyée
 * aux APIs externes — uniquement des requêtes génériques (ex. "medical dashboard").
 */
class SmartImageSelectorService
{
    /**
     * @return array{urls: array<int, string>, fallback: string, source: string}
     */
    public function forScreen(string $screen, string $uiMode = 'normal'): array
    {
        $screen = $this->sanitizeScreen($screen);
        $uiMode = in_array($uiMode, ['normal', 'warning', 'critical'], true) ? $uiMode : 'normal';

        $cacheKey = 'ai_ui:images:'.md5($screen.'|'.$uiMode);
        $ttl = (int) config('ai_ui.cache_ttl_seconds', 300);

        return Cache::remember($cacheKey, $ttl, function () use ($screen, $uiMode) {
            return $this->resolveImages($screen, $uiMode);
        });
    }

    private function sanitizeScreen(string $screen): string
    {
        $allowed = config('ai_ui.screens', []);
        if (in_array($screen, $allowed, true)) {
            return $screen;
        }

        return 'dashboard';
    }

    /**
     * @return array{urls: array<int, string>, fallback: string, source: string}
     */
    private function resolveImages(string $screen, string $uiMode): array
    {
        $fallbacks = config('ai_ui.fallback_images', []);
        $fallback = $fallbacks[$screen] ?? $fallbacks['default'];

        $pexels = (string) config('ai_ui.pexels_api_key', '');
        if ($pexels !== '') {
            try {
                $query = $this->genericQueryForScreen($screen, $uiMode);
                $res = Http::timeout(4)
                    ->withHeaders(['Authorization' => $pexels])
                    ->get('https://api.pexels.com/v1/search', [
                        'query' => $query,
                        'per_page' => 3,
                        'orientation' => 'landscape',
                    ]);
                if ($res->successful()) {
                    $photos = $res->json('photos') ?? [];
                    $urls = [];
                    foreach ($photos as $p) {
                        $src = $p['src']['large'] ?? $p['src']['medium'] ?? null;
                        if (is_string($src) && $src !== '') {
                            $urls[] = $src;
                        }
                    }
                    if (count($urls) >= 1) {
                        while (count($urls) < 3) {
                            $urls[] = $fallback;
                        }

                        return ['urls' => array_slice($urls, 0, 3), 'fallback' => $fallback, 'source' => 'pexels'];
                    }
                }
            } catch (\Throwable $e) {
                Log::debug('ai_ui.pexels_failed', ['error' => $e->getMessage()]);
            }
        }

        $curated = $this->curatedTriple($screen, $fallbacks, $fallback);

        return ['urls' => $curated, 'fallback' => $fallback, 'source' => 'curated'];
    }

    /**
     * Requêtes 100 % génériques (anglais) pour APIs photo.
     */
    private function genericQueryForScreen(string $screen, string $uiMode): string
    {
        $base = match ($screen) {
            'lab_results' => 'laboratory medical analysis',
            'patient_profile', 'patient_timeline' => 'doctor patient consultation',
            'emergency' => 'hospital emergency care',
            'messages' => 'healthcare communication',
            'appointments' => 'medical appointment calendar',
            default => 'medical hospital technology dashboard',
        };

        return $uiMode === 'critical' ? $base.' urgent' : $base;
    }

    /**
     * @param  array<string, string>  $fallbacks
     * @return array<int, string>
     */
    private function curatedTriple(string $screen, array $fallbacks, string $fallback): array
    {
        $a = $fallbacks[$screen] ?? $fallback;
        $b = $fallbacks['dashboard'] ?? $fallback;
        $c = $fallbacks['lab_results'] ?? $fallback;

        return [$a, $b, $c];
    }
}
