<?php

namespace App\Http\Controllers;

use App\Services\AIUIEngineService;
use App\Services\SmartImageSelectorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AIUIController extends Controller
{
    public function __construct(
        private readonly AIUIEngineService $engine,
        private readonly SmartImageSelectorService $images
    ) {}

    /**
     * POST /api/ai-ui/generate
     */
    public function generate(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'screen' => 'nullable|string|max:64',
            'role' => 'nullable|string|max:32',
            'patient_status' => 'nullable|string|in:normal,warning,critical',
            'data_density' => 'nullable|string|in:low,medium,high',
        ]);
        if ($v->fails()) {
            return $this->validationError($v->errors());
        }

        $payload = $this->engine->generateCached($v->validated());

        return $this->success($payload);
    }

    /**
     * POST /api/ai-ui/context
     */
    public function context(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'screen' => 'nullable|string|max:64',
            'role' => 'nullable|string|max:32',
            'patient_status' => 'nullable|string|in:normal,warning,critical',
            'data_density' => 'nullable|string|in:low,medium,high',
        ]);
        if ($v->fails()) {
            return $this->validationError($v->errors());
        }

        $normalized = $this->engine->normalizeContext($v->validated());
        $palette = match ($normalized['patient_status']) {
            'critical' => ['ui_mode' => 'critical', 'primary' => '#EF4444'],
            'warning' => ['ui_mode' => 'warning', 'primary' => '#F59E0B'],
            default => ['ui_mode' => 'normal', 'primary' => '#2563EB'],
        };

        return $this->success([
            'context' => $normalized,
            'preview' => $palette,
        ]);
    }

    /**
     * GET /api/ai-images/screen?screen=dashboard&mode=normal
     */
    public function screenImages(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'screen' => 'required|string|max:64',
            'mode' => 'nullable|string|in:normal,warning,critical',
        ]);
        if ($v->fails()) {
            return $this->validationError($v->errors());
        }

        $screen = (string) $request->query('screen');
        $mode = (string) ($request->query('mode') ?? 'normal');
        $pack = $this->images->forScreen($screen, $mode);

        return $this->success($pack);
    }
}
