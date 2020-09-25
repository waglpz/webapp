<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests\UI;

use Aidphp\Http\ServerRequest;
use Aidphp\Http\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class RestTestCase extends WebTestCase
{
    protected function restGetResponse(string $uri): ResponseInterface
    {
        $app     = $this->createApp();
        $request = new ServerRequest('GET', $uri, ['content-type' => 'application/json']);

        $response = ($app->handleRequest($request))();
        $response->getBody()->rewind();

        return $response;
    }

    protected function restPostResponse(string $uri, ?string $body = null): ResponseInterface
    {
        $app     = $this->createApp();
        $request = new ServerRequest('POST', $uri, ['content-type' => 'application/json']);

        if ($body !== null) {
            $stream = new Stream(\fopen('php://temp', 'wb+'));
            $stream->write($body);
            $request = $request->withBody($stream);
            \assert($request instanceof ServerRequestInterface);
            $request->getBody()->rewind();
        }

        return ($app->handleRequest($request))();
    }
}
