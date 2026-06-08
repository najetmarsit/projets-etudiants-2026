<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMiddleware
{
    private array $compressibleTypes = [
        'application/json',
        'application/javascript',
        'text/css',
        'text/html',
        'text/plain',
        'text/xml',
        'application/xml',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (method_exists($response, 'header')) {
            $response->header('X-Content-Type-Options', 'nosniff');
            $response->header('X-Frame-Options', 'DENY');
            $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');

            if ($this->shouldCompress($response)) {
                $response->header('X-Compression', 'enabled');
            }

            if ($request->isMethod('GET')) {
                $etag = md5($response->getContent() ?: '');
                $response->header('ETag', '"' . $etag . '"');

                $requestEtag = $request->header('If-None-Match');
                if ($requestEtag === '"' . $etag . '"') {
                    return response()->json(null, 304);
                }
            }

            $response->header('Vary', 'Accept-Encoding, Accept-Language');
        }

        return $response;
    }

    private function shouldCompress(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');
        foreach ($this->compressibleTypes as $type) {
            if (str_contains($contentType, $type)) {
                return true;
            }
        }
        return false;
    }
}
