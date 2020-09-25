<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests\UI\Http\Web;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\PhpRenderer;
use Waglpz\Webapp\App;
use Waglpz\Webapp\ExceptionHandler;
use Waglpz\Webapp\Tests\UI\WebTestCase;
use Waglpz\Webapp\UI\Http\Web\SwaggerUI;

final class SwaggerUITest extends WebTestCase
{
    /** @test */
    public function einErrorWirdProduziertWennSchemaFileInvalid(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('file_get_contents(WRONG): failed to open stream: No such file or directory');

        $config = [
            'swagger_scheme_file' => 'WRONG',
            'router'              => static fn () => null,
            'view'                => [
                'layout'     => '',
                'templates'  => '',
                'attributes' => [],
            ],
            'viewHelpers'         => [],
            'exception_handler'   => new ExceptionHandler(),
        ];
        (new App($config));
        $view    = $this->createMock(PhpRenderer::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getRequestTarget')->willReturn('/doc');
        $controller = new SwaggerUI($view);
        $controller($request);
    }

    /** @test */
    public function dokumentation(): void
    {
        $uri      = '/doc';
        $response = $this->webGetResponse($uri);
        self::assertSame(200, $response->getStatusCode());
        $html = (string) $response->getBody();
        self::assertStringContainsString(
            '<title>Waglpz REST API Documentation</title>',
            $html
        );
    }

    /** @test */
    public function schema(): void
    {
        $uri      = '/doc.json';
        $response = $this->webGetResponse($uri);
        self::assertSame(200, $response->getStatusCode());
        $json           = (string) $response->getBody();
        $jsonSchemaFile = \file_get_contents(__DIR__ . '/../../../../swagger.json');
        self::assertIsString($jsonSchemaFile);
        self::assertEquals(
            \json_decode($json, true, 512, \JSON_THROW_ON_ERROR),
            \json_decode($jsonSchemaFile, true, 512, \JSON_THROW_ON_ERROR)
        );
    }
}
