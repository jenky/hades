<?php

namespace Jenky\Hades\Tests\Fixtures;

use Jenky\Hades\Exception\HandlesExceptionResponse;
use Orchestra\Testbench\Exceptions\Handler;

class ExceptionHandler extends Handler
{
    use HandlesExceptionResponse;
}
