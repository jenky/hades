<?php

namespace Jenky\Hades;

use Closure;
use Illuminate\Http\Request;

final class Hades
{
    /**
     * The request identifier callback.
     *
     * @var \Closure|null
     */
    private static $requestIdentifier;

    /**
     * Indicates that the response should always return JSON output.
     */
    public static bool $jsonOutput = false;

    /**
     * The default content negotiation MIME type.
     */
    public static string $mimeType = 'application/json';

    /**
     * Get the request identifier.
     */
    private static function requestIdentifier(): Closure
    {
        return self::$requestIdentifier ?: static function (Request $request) {
            return $request->segment(1) === 'api';
        };
    }

    /**
     * Determines if the request is API request.
     */
    public static function identify(Request $request): bool
    {
        return self::requestIdentifier()($request);
    }

    /**
     * Indicates that the response should always return JSON output.
     *
     * @param  null|\Closure(Request): bool  $when
     */
    public static function forceJsonOutput(?Closure $when = null): self
    {
        if ($when) {
            // Set the callback that will be used to identify the current request.
            self::$requestIdentifier = $when;
        }

        self::$jsonOutput = true;

        return new self();
    }

    /**
     * Set the content negotiation MIME type.
     */
    public function withMimeType(string $type): void
    {
        static::$mimeType = $type;
    }

    /**
     * Set the content negotiation MIME type.
     */
    public function mime(string $type): void
    {
        $this->withMimeType($type);
    }
}
