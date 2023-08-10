<?php

namespace Jenky\Hades;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Jenky\Hades\Http\Middleware\IdentifyRequest;

class HadesServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/hades.php', 'hades');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // @phpstan-ignore-next-line
        $this->app[Kernel::class]->prependMiddleware(IdentifyRequest::class);

        $this->registerPublishing();
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/hades.php' => config_path('hades.php'),
            ], 'config');
        }
    }
}
