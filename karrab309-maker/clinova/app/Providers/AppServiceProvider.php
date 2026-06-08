<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use App\Services\CacheService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CacheService::class, function () {
            return new CacheService();
        });
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        User::observe(UserObserver::class);

        JsonResource::withoutWrapping();

        if ($this->app->environment('local')) {
            DB::listen(function ($query) {
                if ($query->time > 200) {
                    Log::warning('Slow query detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                    ]);
                }
            });
        }

        if ($this->app->runningInConsole()) {
            $this->optimizeCommands();
        }

        if (config('app.force_https', false)) {
            URL::forceScheme('https');
        }
    }

    private function optimizeCommands(): void
    {
        $this->commands([
            \Illuminate\Cache\Console\CacheTableCommand::class,
        ]);

        try {
            if (!Cache::has('app:optimized')) {
                $this->callSilent('route:cache');
                $this->callSilent('config:cache');
                $this->callSilent('view:cache');
                Cache::forever('app:optimized', true);
            }
        } catch (\Throwable $e) {
            Log::warning('Optimization commands skipped (expected in dev): ' . $e->getMessage());
        }
    }
}
