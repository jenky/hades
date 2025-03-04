<?php

namespace Jenky\Hades;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\ServiceProvider;
use Jenky\ApiError\Formatter\ErrorFormatter as ErrorFormatterContract;
use Jenky\ApiError\Formatter\GenericErrorFormatter;
use Jenky\ApiError\Formatter\Rfc7807ErrorFormatter;
use Jenky\ApiError\Handler\Symfony\JsonResponseHandler;
use Jenky\ApiError\Handler\Symfony\ResponseHandler;
use Jenky\ApiError\Transformer\ChainTransformer;
use Jenky\ApiError\Transformer\ExceptionTransformer;
use Jenky\Hades\Http\Middleware\IdentifyRequest;
use Jenky\Hades\Transformer\ValidationExceptionTransformer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HadesServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/hades.php', 'hades');

        $this->app->afterResolving(Handler::class, self::handleExceptionResponse(...));

        /** @var Repository $config */
        $config = $this->app->make('config');

        $this->registerExceptionTransformer($config);

        $this->registerErrorFormatter($config);

        $this->registerResponseHandler($config);
    }

    private function registerErrorFormatter(Repository $config): void
    {
        $this->app->when([
            GenericErrorFormatter::class,
            Rfc7807ErrorFormatter::class,
            ErrorFormatter::class,
        ])
            ->needs('$debug')
            ->give(static fn (Application $app) => $app->hasDebugModeEnabled());

        /** @var string $formatter */
        $formatter = $config->get('hades.formatter', ErrorFormatter::class);

        if (! \is_subclass_of($formatter, ErrorFormatterContract::class, true)) {
            throw new \InvalidArgumentException(sprintf('Error formatter must be instance of %s. %s given', ErrorFormatterContract::class, $formatter));
        }

        $this->app->bind(ErrorFormatterContract::class, $formatter);
    }

    private function registerResponseHandler(Repository $config): void
    {
        /** @var string $responseHandler */
        $responseHandler = $config->get('hades.response_handler', JsonResponseHandler::class);

        if (! \is_subclass_of($responseHandler, ResponseHandler::class, true)) {
            throw new \InvalidArgumentException(sprintf('Response handler must be instance of %s. %s given', ResponseHandler::class, $responseHandler));
        }

        $this->app->bind(ResponseHandler::class, $responseHandler);
    }

    private function registerExceptionTransformer(Repository $config): void
    {
        $transformers = (array) $config->get('hades.transformers', []);
        $transformers[] = ValidationExceptionTransformer::class;

        $this->app->tag($transformers, ['api_error.exception_transformer']);

        $this->app->when(ChainTransformer::class)
            ->needs('$transformers')
            ->giveTagged('api_error.exception_transformer');

        $this->app->bind(ExceptionTransformer::class, ChainTransformer::class);
    }

    protected static function handleExceptionResponse(Handler $handler, Application $app): void
    {
        $responseHandler = $app->make(ResponseHandler::class);

        $handler->renderable(static function (\Throwable $e, Request $request) use ($responseHandler) {
            // Custom logic to migrate the behavior of `Handler::render()`
            if ($e instanceof HttpResponseException) {
                return $e->getResponse();
            }

            if ($e instanceof AuthenticationException) {
                $e = new HttpException(401, $e->getMessage(), $e);
            }

            return $responseHandler->render($e, $request);
        });
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
