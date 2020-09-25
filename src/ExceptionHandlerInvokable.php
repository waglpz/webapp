<?php

declare(strict_types=1);

namespace Waglpz\Webapp;

use Interop\Http\EmitterInterface;

interface ExceptionHandlerInvokable
{
    public function __invoke(\Throwable $exception, ?EmitterInterface $emitter = null): void;
}
