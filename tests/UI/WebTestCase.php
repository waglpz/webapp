<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests\UI;

use Aidphp\Http\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Waglpz\Webapp\App;
use Waglpz\Webapp\DbConnection;

abstract class WebTestCase extends TestCase
{
    use DbConnection;

    /** @return array<mixed> */
    public function config() : array
    {
        return include \dirname(__DIR__) . '/../config/main.php';
    }

    /** @param ?array<mixed> $replaceConfigValues */
    public function createApp(?array $replaceConfigValues = null) : App
    {
        $_SERVER['REQUEST_SCHEME'] = 'http';
        $_SERVER['HTTP_HOST']      = 'localhost';
        $_SESSION                  = [];
        $_COOKIE                   = [];
        $_REQUEST                  = [];

        if ($replaceConfigValues !== null) {
            $config = \array_replace_recursive($this->config(), $replaceConfigValues);
        } else {
            $config = $this->config();
        }

        return new App($config);
    }

    protected function webGetResponse(string $uri) : ResponseInterface
    {
        $app     = $this->createApp();
        $request = new ServerRequest('GET', $uri, ['content-type' => 'text/html']);

        return ($app->handleRequest($request))();
    }
}
