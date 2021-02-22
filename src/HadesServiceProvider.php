<?php

namespace Jenky\Hades;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Jenky\Hades\Http\Middleware\IdentifyRequest;

class HadesServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/hades.php', 'hades');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app[Kernel::class]->prependMiddleware(IdentifyRequest::class);

        $this->registerPublishing();
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/hades.php' => config_path('hades.php'),
            ], 'config');
        }
    }
}
