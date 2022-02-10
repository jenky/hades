<?php

namespace Jenky\Hades\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Jenky\Hades\Hades;

class ResponseTest extends FeatureTestCase
{
    use WithFaker;

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('hades.debug.trace_as_string', true);
    }

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadRoutes();
    }

    /**
     * Set up routes.
     *
     * @return void
     */
    protected function loadRoutes()
    {
        Route::prefix('api/v1')
            ->group(function () {
                Route::middleware('auth:api')->get('user', function (Request $request) {
                    return $request->user();
                });

                Route::post('register', function (Request $request) {
                    $request->validate([
                        'email' => 'required|email',
                        'name' => 'required|min:2',
                        'password' => 'required|min:8|confirmed',
                    ]);

                    return response()->noContent();
                });

                Route::post('post', function () {
                    abort(403);
                });

                Route::put('post', function () {
                    throw new \InvalidArgumentException;
                });

                Route::get('internal-error', function () {
                    abort(500);
                });

                Route::get('errors', function () {
                    request()->validate([
                        'q' => 'required',
                    ]);

                    return ['ok' => true];
                });
            });

        Route::get('exception', function () {
            throw new \Exception;
        });
    }

    /**
     * Get error response structure.
     *
     * @return array
     */
    protected function getJsonStructure()
    {
        $structure = Hades::errorFormat();

        if (! $this->app['config']->get('app.debug')) {
            Arr::forget($structure, 'debug');
        }

        return $structure;
    }

    public function test_authentication()
    {
        $this->getJson('api/v1/user')
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
                'status_code' => 401,
                'type' => 'AuthenticationException',
            ]);
    }

    public function test_not_found_response()
    {
        $structure = $this->getJsonStructure();
        Arr::forget($structure, 'errors');

        $this->getJson('api/v1/not-found')
            ->assertNotFound()
            ->assertJsonStructure(array_keys($structure))
            ->assertJson([
                'type' => 'NotFoundHttpException',
                'status_code' => 404,
            ]);
    }

    public function test_validation_errors_response()
    {
        $this->getJson('api/v1/errors')
            ->assertStatus(422)
            ->assertJsonStructure(array_keys($this->getJsonStructure()))
            ->assertJsonValidationErrors([
                'q',
            ]);

            $this->postJson('api/v1/register')
            ->assertStatus(422)
            ->assertJson([
                // 'message' => 'The given data was invalid.',
                'status_code' => 422,
                'type' => 'ValidationException',
            ])
            ->assertJsonValidationErrors([
                'email', 'name', 'password',
            ]);

        $this->postJson('api/v1/register', [
            'email' => $this->faker()->email,
            'name' => $this->faker()->name,
            'password' => $password = Str::random(10),
            'password_confirmation' => $password,
        ])->assertStatus(204);
    }

    public function test_error_response()
    {
        $structure = $this->getJsonStructure();
        Arr::forget($structure, 'errors');

        $this->getJson('api/v1/internal-error')
            ->assertStatus(500)
            ->assertJsonStructure(array_keys($structure))
            ->assertJson([
                'type' => 'HttpException',
                'status_code' => 500,
            ]);
    }

    public function test_client_error()
    {
        $this->postJson('api/v1/post')
            ->assertForbidden()
            ->assertJson([
                'message' => 'Forbidden',
                'status_code' => 403,
                'type' => 'HttpException',
            ]);
    }

    public function test_server_error()
    {
        $this->putJson('api/v1/post')
            ->assertStatus(500)
            ->assertJson([
                'message' => 'Internal Server Error',
                'status_code' => 500,
                'type' => 'InvalidArgumentException',
            ]);
    }

    public function test_json_output_without_content_negotiation()
    {
        // Create login route to avoid 500 internal error on failed login redirect.
        Route::get('login', function () {
            return 'login';
        })->name('login');

        $this->get('api/v1/user')
            ->assertRedirect();

        Hades::forceJsonOutput();

        $this->get('api/v1/user')
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
                'status_code' => 401,
                'type' => 'AuthenticationException',
            ]);

        $this->get('exception')
            ->assertStatus(500);

        Hades::forceJsonOutput(function (Request $request) {
            return true;
        })->withMimeType('application/vnd.hades.v1+json');

        $this->get('exception')
            ->assertStatus(500)
            ->assertJson([
                'message' => 'Internal Server Error',
                'status_code' => 500,
                'type' => 'Exception',
            ]);
    }

    public function test_custom_error_format()
    {
        Hades::errorFormat([
            'message' => ':message',
            'code' => ':code',
        ]);

        $this->getJson('api/v1/user')
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => 'Unauthenticated.',
                'code' => 0,
            ]);
    }
}
