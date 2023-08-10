<?php

namespace Jenky\Hades\Tests;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Jenky\Hades\HadesServiceProvider;
use Jenky\Hades\Tests\Fixtures\ExceptionHandler as Handler;
use Orchestra\Testbench\TestCase;

class FeatureTestCase extends TestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            HadesServiceProvider::class,
        ];
    }

    /**
     * Resolve application HTTP exception handler.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function resolveApplicationExceptionHandler($app)
    {
        $app->singleton(ExceptionHandler::class, Handler::class);
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $config = $app->get('config');

        $config->set('database.default', 'testbench');

        $config->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        if (! $config->get('auth.guards.api')) {
            $config->set('auth.guards.api', [
                'driver' => 'token',
                'provider' => 'users',
                'hash' => false,
            ]);
        }

        $config->set('app.debug', true);
    }
}
