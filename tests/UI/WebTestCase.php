<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests\UI;

use Aidphp\Http\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Waglpz\Webapp\App;

use function Waglpz\DiContainer\container;

abstract class WebTestCase extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function createApp(): App
    {
        $_SERVER['REQUEST_SCHEME'] = 'http';
        $_SERVER['HTTP_HOST']      = 'localhost';
        $_SESSION                  = [];
        $_COOKIE                   = [];
        $_REQUEST                  = [];

        $container = container();
        $app       = $container->get(App::class);
        \assert($app instanceof App);

        return $app;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function webGetResponse(string $uri): ResponseInterface
    {
        $app     = $this->createApp();
        $request = new ServerRequest('GET', $uri, ['content-type' => 'text/html']);

        return ($app->handleRequest($request))();
    }
}
