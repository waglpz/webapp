<?php

declare(strict_types=1);

namespace Waglpz\Webapp;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class BaseController
{
    abstract public function __invoke(ServerRequestInterface $request): ResponseInterface;
}
