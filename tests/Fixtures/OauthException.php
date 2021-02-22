<?php

namespace Jenky\Hades\Tests\Fixtures;

use Jenky\Hades\Exception\HasType;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OauthException extends HttpException
{
    use HasType;
}
