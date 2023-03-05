<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests;

use Aidphp\Http\Response;
use FastRoute\Dispatcher;
use Interop\Http\EmitterInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Waglpz\Webapp\App;

final class AppHandledRequestTest extends TestCase
{
    /**
     * @throws Exception
     *
     * @test
     */
    public function returnsAHandlerAndEmitResponse(): void
    {
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->with('GET', '/')
            ->willReturn(
                [
                    0 => Dispatcher::FOUND,
                    1 => 'handler',
                    2 => ['rp' => 'abc'],
                ],
            );
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::exactly(2))
                  ->method('get')
                  ->willReturn(static fn () => new \Aidphp\Http\Response());
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::exactly(2))->method('getMethod')->willReturn('GET');
        $request->expects(self::exactly(2))->method('getRequestTarget')->willReturn('/?param=val');
        $request->expects(self::exactly(2))->method('withAttribute')->willReturnSelf();
        $emitter = $this->createMock(EmitterInterface::class);
        $emitter->expects(self::once())->method('emit')->with(self::isInstanceOf(ResponseInterface::class));
        $app = new App($dispatcher, $emitter);
        $app->setContainer($container);
        $response = ($app->handleRequest($request))();
        self::assertSame(200, $response->getStatusCode());
        $app->run($request);
    }

    /**
     * @throws Exception
     *
     * @test
     */
    public function returnsAHandlerResponse(): void
    {
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->with('GET', '/')
            ->willReturn(
                [
                    0 => Dispatcher::FOUND,
                    1 => static fn () => new Response(),
                    2 => ['rp' => 'abc'],
                ],
            );
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::never())
                  ->method('get')
                  ->willReturn(static fn () => new \Aidphp\Http\Response());
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::exactly(2))->method('getMethod')->willReturn('GET');
        $request->expects(self::exactly(2))->method('getRequestTarget')->willReturn('/?param=val');
        $request->expects(self::exactly(2))->method('withAttribute')->willReturnSelf();
        $emitter = $this->createMock(EmitterInterface::class);
        $emitter->expects(self::once())->method('emit')->with(self::isInstanceOf(ResponseInterface::class));
        $app = new App($dispatcher, $emitter);
        $app->setContainer($container);
        $handler  = $app->handleRequest($request);
        $response = $handler();
        self::assertSame(200, $response->getStatusCode());
        $app->run($request);
    }

    /**
     * @throws Exception
     *
     * @test
     */
    public function returnsAHandler(): void
    {
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->with('GET', '/')
            ->willReturn(
                [
                    0 => Dispatcher::FOUND,
                    1 => 'handler',
                    2 => ['rp' => 'abc'],
                ],
            );
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::exactly(2))
                  ->method('get')
                  ->willReturn(static fn () => new \Aidphp\Http\Response());
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::exactly(2))->method('getMethod')->willReturn('GET');
        $request->expects(self::exactly(2))->method('getRequestTarget')->willReturn('/?param=val');
        $request->expects(self::exactly(2))->method('withAttribute')->willReturnSelf();
        $emitter = $this->createMock(EmitterInterface::class);
        $emitter->expects(self::once())->method('emit')->with(self::isInstanceOf(ResponseInterface::class));
        $app = new App($dispatcher, $emitter);
        $app->setContainer($container);
        $response = ($app->handleRequest($request))();

        self::assertSame(200, $response->getStatusCode());
        $app->run($request);
    }

    /**
     * @throws Exception
     *
     * @test
     */
    public function andProducesError405MethodNotAllowed(): void
    {
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with('GET', '/')
            ->willReturn(
                [
                    0 => Dispatcher::METHOD_NOT_ALLOWED,
                    1 => ['POST'],
                ],
            );

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('GET');
        $request->expects(self::once())->method('getRequestTarget')->willReturn('/');
        $emitter = $this->createMock(EmitterInterface::class);
        $emitter->expects(self::never())->method('emit');
        $app = new App($dispatcher, $emitter);

        $this->expectException(\Error::class);
        $this->expectExceptionCode(405);
        $this->expectExceptionMessage(
            'Unfortunately requested HTTP method "GET" not allowed. Allowed are "POST".',
        );

        $app->handleRequest($request);
    }

    /**
     * @throws Exception
     *
     * @test
     */
    public function andProducesError404NotFound(): void
    {
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with('GET', '/')
            ->willReturn([0 => Dispatcher::NOT_FOUND]);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('GET');
        $request->expects(self::once())->method('getRequestTarget')->willReturn('/');
        $emitter = $this->createMock(EmitterInterface::class);
        $emitter->expects(self::never())->method('emit');

        $app = new App($dispatcher, $emitter);

        $this->expectException(\Error::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('Unfortunately requested site or resource "/" does not exist!');

        $app->handleRequest($request);
    }

    /**
     * @throws Exception
     *
     * @test
     */
    public function andProduces500UnknownServerErrors(): void
    {
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with('GET', '/')
            ->willReturn([]);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('GET');
        $request->expects(self::once())->method('getRequestTarget')->willReturn('/');

        $emitter = $this->createMock(EmitterInterface::class);
        $emitter->expects(self::never())->method('emit');

        $app = new App($dispatcher, $emitter);

        $this->expectException(\Error::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('Unknown server error, router problem.');

        $app->handleRequest($request);
    }
}
