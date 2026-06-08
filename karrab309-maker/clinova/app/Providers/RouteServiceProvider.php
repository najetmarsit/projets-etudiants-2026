<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $perMinute = (int) config('optimization.security.api_rate_limit', 60);
            $decayMinutes = max(1, (int) config('optimization.security.api_rate_limit_decay', 1));

            return Limit::perMinutes($decayMinutes, $perMinute)
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            $maxAttempts = (int) config('optimization.security.brute_force_throttle', 5);
            $decayMinutes = max(1, (int) config('optimization.security.brute_force_decay', 1));
            $username = (string) $request->input('username', '');

            return Limit::perMinutes($decayMinutes, $maxAttempts)
                ->by($request->ip() . '|' . strtolower($username));
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
