<?php

declare(strict_types=1);

namespace Jenky\Hades\Transformer;

use Illuminate\Validation\ValidationException;
use Jenky\ApiError\GenericProblem;
use Jenky\ApiError\Problem;
use Jenky\ApiError\Transformer\ExceptionTransformer;

final class ValidationExceptionTransformer implements ExceptionTransformer
{
    public function transform(\Throwable $exception): Problem
    {
        \assert($exception instanceof ValidationException);

        return GenericProblem::createFromThrowable($exception, $exception->status)
            ->setMessage('Validation errors.')
            ->set('errors', $exception->errors());
    }
}
