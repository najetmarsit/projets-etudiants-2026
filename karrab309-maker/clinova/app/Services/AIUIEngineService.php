<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Moteur UI Clinova — règles déterministes + JSON structuré pour le client Flutter.
 *
 * Extension future : brancher un LLM ou un service ML en remplacement de buildLayout().
 * Aucune donnée nominative patient dans la sortie par défaut.
 */
class AIUIEngineService
{
    public function __construct(
        private readonly SmartImageSelectorService $images
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function normalizeContext(array $input): array
    {
        $screen = $this->sanitizeScreen((string) ($input['screen'] ?? 'dashboard'));
        $role = $this->sanitizeRole((string) ($input['role'] ?? 'Doctor'));
        $patientStatus = $this->sanitizeStatus((string) ($input['patient_status'] ?? 'normal'));
        $dataDensity = $this->sanitizeDensity((string) ($input['data_density'] ?? 'medium'));

        return [
            'screen' => $screen,
            'role' => $role,
            'patient_status' => $patientStatus,
            'data_density' => $dataDensity,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function generate(array $input): array
    {
        $ctx = $this->normalizeContext($input);
        $screen = $ctx['screen'];
        $patientStatus = $ctx['patient_status'];
        $dataDensity = $ctx['data_density'];
        $role = $ctx['role'];

        $uiMode = match ($patientStatus) {
            'critical' => 'critical',
            'warning' => 'warning',
            default => 'normal',
        };

        $theme = match ($uiMode) {
            'critical' => 'emergency_red',
            'warning' => 'amber_clinical',
            default => 'medical_blue_teal',
        };

        $layout = match ($dataDensity) {
            'high' => 'compact_priority',
            'low' => 'spacious_reading',
            default => 'balanced_clinical',
        };

        $palette = $this->paletteForMode($uiMode);

        $components = $this->componentsFor($screen, $uiMode, $role);
        $priorityOrder = $this->priorityOrderFor($screen, $uiMode);

        $imagePack = $this->images->forScreen($screen, $uiMode);

        return [
            'version' => 1,
            'context' => $ctx,
            'theme' => $theme,
            'layout' => $layout,
            'ui_mode' => $uiMode,
            'primary_color' => $palette['primary'],
            'secondary_color' => $palette['secondary'],
            'background' => $palette['background'],
            'text' => $palette['text'],
            'accent_warning' => $palette['warning'],
            'accent_critical' => $palette['critical'],
            'images' => [
                'urls' => $imagePack['urls'],
                'fallback' => $imagePack['fallback'],
                'source' => $imagePack['source'],
            ],
            'image_hints' => $this->imageHints($screen),
            'components' => $components,
            'priority_order' => $priorityOrder,
            'ux_rules' => [
                'max_hero_images' => 1,
                'progressive_disclosure' => true,
                'medical_clean' => true,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function paletteForMode(string $uiMode): array
    {
        return match ($uiMode) {
            'critical' => [
                'primary' => '#EF4444',
                'secondary' => '#B91C1C',
                'background' => '#FEF2F2',
                'text' => '#0F172A',
                'warning' => '#F59E0B',
                'critical' => '#DC2626',
            ],
            'warning' => [
                'primary' => '#F59E0B',
                'secondary' => '#14B8A6',
                'background' => '#FFFBEB',
                'text' => '#0F172A',
                'warning' => '#D97706',
                'critical' => '#EF4444',
            ],
            default => [
                'primary' => '#2563EB',
                'secondary' => '#14B8A6',
                'background' => '#F8FAFC',
                'text' => '#0F172A',
                'warning' => '#F59E0B',
                'critical' => '#EF4444',
            ],
        };
    }

    /**
     * @return list<string>
     */
    private function componentsFor(string $screen, string $uiMode, string $role): array
    {
        $base = ['SmartCard'];
        if ($uiMode === 'critical') {
            $base[] = 'AlertCard';
        }
        if (in_array($screen, ['patient_profile', 'patient_timeline'], true)) {
            $base[] = 'AIImageHeader';
            $base[] = 'SmartTimeline';
        }
        if ($screen === 'dashboard') {
            $base[] = 'AIImageHeader';
            $base[] = 'SmartMetricTile';
        }
        if ($screen === 'lab_results') {
            $base[] = 'LabResultCard';
        }
        if (in_array($screen, ['alerts', 'emergency'], true)) {
            $base[] = 'AlertCard';
        }

        if ($role === 'Nurse' && $screen === 'dashboard') {
            $base[] = 'SmartTimeline';
        }

        return array_values(array_unique($base));
    }

    /**
     * @return list<string>
     */
    private function priorityOrderFor(string $screen, string $uiMode): array
    {
        if ($uiMode === 'critical') {
            return ['alerts', 'summary', 'history'];
        }

        return match ($screen) {
            'dashboard' => ['metrics', 'appointments', 'alerts', 'history'],
            'patient_profile', 'patient_timeline' => ['summary', 'timeline', 'labs', 'messages'],
            'lab_results' => ['results', 'history', 'patient_summary'],
            'emergency' => ['alerts', 'vitals', 'contacts'],
            default => ['summary', 'actions', 'history'],
        };
    }

    /**
     * @return list<string>
     */
    private function imageHints(string $screen): array
    {
        return match ($screen) {
            'lab_results' => ['blood_test', 'lab_analysis', 'microscope'],
            'emergency' => ['emergency_room', 'doctor_action', 'monitor'],
            'patient_profile' => ['doctor_consultation', 'patient_care', 'clipboard'],
            default => ['medical_analytics', 'hospital_ui', 'team_care'],
        };
    }

    private function sanitizeScreen(string $screen): string
    {
        $allowed = config('ai_ui.screens', []);

        return in_array($screen, $allowed, true) ? $screen : 'dashboard';
    }

    private function sanitizeRole(string $role): string
    {
        $allowed = config('ai_ui.roles', []);

        return in_array($role, $allowed, true) ? $role : 'Doctor';
    }

    private function sanitizeStatus(string $status): string
    {
        return in_array($status, ['normal', 'warning', 'critical'], true) ? $status : 'normal';
    }

    private function sanitizeDensity(string $d): string
    {
        return in_array($d, ['low', 'medium', 'high'], true) ? $d : 'medium';
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function cacheKey(array $input): string
    {
        $ctx = $this->normalizeContext($input);

        return 'ai_ui:generate:'.md5(json_encode($ctx));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function generateCached(array $input): array
    {
        if (! config('ai_ui.enabled', true)) {
            return $this->fallbackPayload($this->normalizeContext($input));
        }

        $key = $this->cacheKey($input);
        $ttl = (int) config('ai_ui.cache_ttl_seconds', 300);

        return Cache::remember($key, $ttl, fn () => $this->generate($input));
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @return array<string, mixed>
     */
    private function fallbackPayload(array $ctx): array
    {
        $payload = $this->generate(array_merge($ctx, ['patient_status' => 'normal', 'data_density' => 'medium']));
        $payload['degraded'] = true;
        $payload['message'] = 'AI UI engine disabled — safe defaults applied.';

        return $payload;
    }
}
