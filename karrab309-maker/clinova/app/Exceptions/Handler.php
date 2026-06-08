<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render exception for API requests (JSON response).
     */
    public function render($request, Throwable $e)
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->renderApiException($request, $e);
        }
        return parent::render($request, $e);
    }

    protected function renderApiException(Request $request, Throwable $e)
    {
        $status = 500;
        $message = config('app.debug') ? $e->getMessage() : 'An error occurred.';

        if ($e instanceof ValidationException) {
            $status = 422;
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], $status);
        }

        if ($e instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'Session expirée. Veuillez vous reconnecter.',
            ], 401);
        }

        if ($e instanceof HttpException) {
            $status = $e->getStatusCode();
            $message = $e->getMessage() ?: $this->getHttpMessage($status);
        }

        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    protected function getHttpMessage(int $status): string
    {
        return match ($status) {
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            422 => 'Validation error',
            429 => 'Too many requests',
            500 => 'Internal server error',
            default => 'An error occurred',
        };
    }
}
