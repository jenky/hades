<?php

namespace Jenky\Hades\Exception;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

trait HandlesExceptionResponse
{
    use FormatsException;

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $e)
    {
        $response = parent::unauthenticated($request, $e);

        return $this->expectsJson($request, $response)
            ? $this->toJsonResponse($e, 401)
            : $response;
    }

    /**
     * Create a response object from the given validation exception.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function convertValidationExceptionToResponse(ValidationException $e, $request)
    {
        $response = parent::convertValidationExceptionToResponse($e, $request);

        return $this->expectsJson($request, $response)
            ? $this->toJsonResponse($e, $e->status)
            : $response;
    }

    /**
     * Prepare a JSON response for the given exception.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function prepareJsonResponse($request, Throwable $e)
    {
        return $this->toJsonResponse($e);
    }

    /**
     * Determine if the current request expects a JSON response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response
     */
    protected function expectsJson(Request $request, Response $response): bool
    {
        return $response instanceof JsonResponse;
    }
}
