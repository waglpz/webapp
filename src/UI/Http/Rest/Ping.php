<?php

declare(strict_types=1);

namespace Waglpz\Webapp\UI\Http\Rest;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Waglpz\Webapp\BaseController;

use function Waglpz\Webapp\config;

final class Ping extends BaseController
{
    /**
     * @throws \JsonException
     */
    public function __invoke(RequestInterface $request): ResponseInterface
    {
        $data = [
            'time'       => \microtime(true),
            'apiVersion' => config('apiVersion'),
        ];

        return $this->renderJson($data);
    }
}
