<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuditMiddleware
{
    private array $sensitiveMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];
    private array $sensitiveRoutes = [
        'patients',
        'operations',
        'health-indicators',
        'messages',
        'alerts',
        'admin/users',
        'payments',
        'inventory-movements',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldLog($request)) {
            $this->logRequest($request, $response);
        }

        return $response;
    }

    private function shouldLog(Request $request): bool
    {
        if (!in_array($request->method(), $this->sensitiveMethods)) {
            return false;
        }

        foreach ($this->sensitiveRoutes as $route) {
            if (str_contains($request->path(), $route)) {
                return true;
            }
        }

        return false;
    }

    private function logRequest(Request $request, Response $response): void
    {
        $user = Auth::user();
        $data = [
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_role' => $user?->role,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $response->getStatusCode(),
        ];

        if ($response->getStatusCode() >= 400) {
            $data['response_body'] = mb_substr($response->getContent() ?: '', 0, 1000);
        }

        Log::channel('audit')->info('API Action', $data);
    }
}
