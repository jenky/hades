<?php

namespace Jenky\Hades;

use Closure;
use Illuminate\Http\Request;

class Hades
{
    /**
     * The request identifier callback.
     *
     * @var \Closure
     */
    protected static $requestIdentifier;

    /**
     * Indicates that the response should always return JSON output.
     *
     * @var bool
     */
    public static $jsonOutput = false;

    /**
     * The default content negotiation MIME type.
     *
     * @var string
     */
    public static $mimeType = 'application/json';

    /**
     * Generic error response format.
     *
     * @var array
     */
    public static $errorFormat = [
        'message' => ':message',
        'type' => ':type',
        'status_code' => ':status_code',
        'errors' => ':errors',
        'code' => ':code',
        'debug' => ':debug',
    ];

    /**
     * Get the request identifier.
     *
     * @return \Closure
     */
    public static function requestIdentifier(): Closure
    {
        return static::$requestIdentifier ?: function (Request $request) {
            return $request->segment(1) === 'api';
        };
    }

    /**
     * Determines if the request is API request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public static function identify(Request $request): bool
    {
        return call_user_func(static::requestIdentifier(), $request);
    }

    /**
     * Indicates that the response should always return JSON output.
     *
     * @param  null|string  $contentType
     * @return static
     */
    public static function forceJsonOutput(?Closure $when = null)
    {
        if ($when) {
            // Set the callback that will be used to identify the current request.
            static::$requestIdentifier = $when;
        }

        static::$jsonOutput = true;

        return new static();
    }

    /**
     * Set the content negotiation MIME type.
     *
     * @param  string  $type
     * @return $this
     */
    public function withMimeType(string $type)
    {
        static::$mimeType = $type;

        return $this;
    }

    /**
     * Get or set the error response format.
     *
     * @param  array  $format
     * @return array|static
     */
    public static function errorFormat(array $format = [])
    {
        if (empty($format)) {
            return static::$errorFormat;
        }

        static::$errorFormat = $format;

        return new static();
    }
}
