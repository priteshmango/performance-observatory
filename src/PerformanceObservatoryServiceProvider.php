<?php

namespace Performance\Observatory;

use Illuminate\Support\ServiceProvider;

class PerformanceObservatoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/observatory.php', 'observatory'
        );

        $this->app->singleton(ObservatoryManager::class, function ($app) {
            return new ObservatoryManager($app);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/observatory.php' => config_path('observatory.php'),
        ], 'observatory-config');

        $this->publishes([
            __DIR__ . '/../resources/js/tracker.js' => public_path('vendor/observatory/tracker.js'),
        ], 'observatory-assets');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'observatory');

        // Boot the manager to start collecting if enabled
        if (config('observatory.enabled') && $this->shouldSample()) {
            $this->app->make(ObservatoryManager::class)->boot();
        }
    }

    /**
     * Determine if this request should be sampled.
     */
    protected function shouldSample(): bool
    {
        // Don't track observatory's own internal routes or common debug tools
        $prefix = config('observatory.route_prefix', 'observatory');
        $ignoredPaths = [
            $prefix,
            $prefix . '/*',
            '_debugbar/*',
            'vendor/*',
            'build/*',
            'livewire/*',
            'horizon/*',
            'telescope/*'
        ];

        foreach ($ignoredPaths as $ignoredPath) {
            if (request()->is($ignoredPath)) {
                return false;
            }
        }

        $sampleRate = config('observatory.sample_rate', 100);
        if ($sampleRate >= 100) {
            return true;
        }

        return random_int(1, 100) <= $sampleRate;
    }
}
