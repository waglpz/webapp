<?php

declare(strict_types=1);

namespace Waglpz\Webapp\UI\Http\Rest;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Waglpz\Webapp\App;
use Waglpz\Webapp\BaseController;

final class Ping extends BaseController
{
    public function __invoke(RequestInterface $request) : ResponseInterface
    {
        $data = [
            'time'       => \microtime(true),
            'apiVersion' => App::getConfig('apiVersion'),
        ];

        return $this->renderJson($data);
    }
}
