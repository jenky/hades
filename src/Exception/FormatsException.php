<?php

namespace Jenky\Hades\Exception;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ReflectsClosures;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Jenky\Hades\Hades;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Throwable;

trait FormatsException
{
    use ReflectsClosures;

    /**
     * The registered exception callbacks.
     *
     * @var array<class-string, callable>
     */
    protected $exceptionCallbacks = [];

    /**
     * The exception error replacements.
     *
     * @var array<string, mixed>
     */
    protected $replacements = [];

    /**
     * Map exception into an JSON response.
     *
     * @param  \Throwable  $e
     * @return \Illuminate\Http\JsonResponse
     */
    public function toJsonResponse(Throwable $exception, int $statusCode = null, array $headers = [])
    {
        $replacements = $this->prepareReplacements(
            $exception, $statusCode, $headers
        );

        $response = Hades::errorFormat();

        array_walk_recursive($response, function (&$value) use ($replacements) {
            if (Str::startsWith($value, ':') && isset($replacements[$value])) {
                $value = $replacements[$value];
            }
        });

        /** @var FlattenException $exception */
        return new JsonResponse(
            $this->removeEmptyReplacements($response),
            $exception->getStatusCode(),
            $exception->getHeaders(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * Prepare the replacements array by gathering the keys and values.
     */
    protected function prepareReplacements(Throwable &$exception, int $statusCode = null, array $headers = []): array
    {
        $e = FlattenException::createFromThrowable($exception, $statusCode, $headers);

        $replacements = [
            ':message' => $e->getMessage() ?: $e->getStatusText(),
            ':status_code' => $e->getStatusCode(),
            ':type' => class_basename($e->getClass()),
            ':code' => $e->getCode(),
        ];

        if ($exception instanceof ValidationException) {
            $validator = $exception->validator;

            if ($validator->errors()->isNotEmpty()) {
                $replacements[':errors'] = $validator->errors();
            }
        }

        if ($this->runningInDebugMode() && ! $this->runningInProduction()) {
            $replacements[':debug'] = $this->appendDebugInformation($e);
        }

        foreach ($this->exceptionCallbacks as $th => $callback) {
            if (is_a($exception, $th, true)) {
                $callback($exception, $this);
            }
        }

        $exception = $e;

        return array_merge($replacements, $this->getReplacements());
    }

    /**
     * Appends debug information.
     */
    protected function appendDebugInformation(FlattenException $e): array
    {
        $trace = $this->config('hades.debug.trace_as_string', false)
            ? explode("\n", $e->getTraceAsString())
            : (
                $this->config('hades.debug.trace_args', false)
                ? $e->getTrace()
                : array_map(function ($item) {
                    return Arr::except($item, ['args']);
                }, $e->getTrace())
            );

        if ($size = (int) $this->config('hades.debug.trace_size_limit', 0)) {
            $trace = array_splice($trace, 0, $size);
        }

        return [
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'class' => $e->getClass(),
            'trace' => $trace,
        ];
    }

    /**
     * Recursively remove any empty replacement values in the response array.
     */
    protected function removeEmptyReplacements(array $input): array
    {
        foreach ($input as &$value) {
            if (is_array($value)) {
                $value = $this->removeEmptyReplacements($value);
            }
        }

        return array_filter($input, function ($value) {
            if (is_string($value)) {
                return ! Str::startsWith($value, ':');
            }

            return true;
        });
    }

    /**
     * Determines if the application are running in debug mode.
     */
    protected function runningInDebugMode(): bool
    {
        return (bool) $this->config('app.debug', false);
    }

    /**
     * Determines if the application are running in production environment.
     */
    protected function runningInProduction(): bool
    {
        return $this->container->make(Application::class)->isProduction();
    }

    /**
     * Get the config instance or value.
     *
     * @param  string  $key
     * @param  mixed|null  $default
     * @return mixed
     */
    protected function config(string $key = null, $default = null)
    {
        return $this->container->make('config')->get($key, $default);
    }

    /**
     * Get user defined replacements.
     */
    public function getReplacements(): array
    {
        return $this->replacements;
    }

    /**
     * Set user defined replacements.
     *
     * @return $this
     */
    public function setReplacements(array $replacements)
    {
        $this->replacements = $replacements;

        return $this;
    }

    /**
     * Replace given key with value.
     *
     * @param  mixed  $value
     * @return $this
     */
    public function replace(string $key, $value)
    {
        $this->replacements[Str::start($key, ':')] = $value;

        return $this;
    }

    /**
     * Register callback for given exception.
     *
     * @param  \Closure|string  $exception
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function catch($exception, Closure $callback = null)
    {
        if (is_callable($exception) && is_null($callback)) {
            $exception = $this->firstClosureParameterType($callback = $exception);
        }

        if (is_string($exception) && ! $callback) {
            throw new InvalidArgumentException('Invalid exception mapping.');
        }

        $this->exceptionCallbacks[$exception] = $callback;

        return $this;
    }
}
