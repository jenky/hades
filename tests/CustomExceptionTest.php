<?php

namespace Jenky\Hades\Tests;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Route;

final class CustomExceptionTest extends FeatureTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadRoutes();
    }

    private function loadRoutes(): void
    {
        Route::prefix('api/v1')
            ->group(function () {
                Route::get('exception-type', function () {
                    throw (new OauthException(400, 'The grant type is not available for your client!', code: 100));
                });

                Route::get('exception-callback', function () {
                    throw new AuthenticationException();
                });
            });
    }

    public function test_exception_has_type()
    {
        $this->getJson('api/v1/exception-type')
            ->assertStatus(400)
            ->assertJson([
                'message' => 'The grant type is not available for your client!',
                'status' => 400,
                // 'code' => 100,
            ]);
    }

    public function test_exception_callback()
    {
        $this->getJson('api/v1/exception-callback')
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
                // 'code' => 0,
                'status' => 401,
            ]);

        $this->app->make(ExceptionHandler::class)->map(AuthenticationException::class, static fn () => new AuthenticationException('Authentication failed.'));

        $this->getJson('api/v1/exception-callback')
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Authentication failed.',
                // 'code' => 0,
                'status' => 401,
            ]);
    }
}
