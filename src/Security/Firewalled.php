<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Security;

use Psr\Http\Message\ServerRequestInterface;

interface Firewalled
{
    public function checkRules(ServerRequestInterface $request): void;
}
