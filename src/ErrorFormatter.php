<?php

declare(strict_types=1);

namespace Jenky\Hades;

use Jenky\ApiError\Formatter\AbstractErrorFormatter;
use Jenky\ApiError\GenericProblem;
use Jenky\ApiError\Problem;

final class ErrorFormatter extends AbstractErrorFormatter
{
    protected function getFormat(): array
    {
        $format = [
            'message' => '{title}',
            'status' => '{status_code}',
            // 'code' => '{code}',
            'errors' => '{errors}',
        ];

        if ($this->debug) {
            $format['debug'] = '{debug}';
        }

        return $format;
    }

    protected function createProblem(\Throwable $exception): Problem
    {
        return GenericProblem::createFromThrowable($exception);
    }
}
