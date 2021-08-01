<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests;

use FastRoute\Dispatcher;
use Interop\Http\EmitterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Waglpz\Webapp\App;

final class AppHandledRequestTest extends TestCase
{
    /**
     * @test
     */
    public function returnEinHandlerUndEmitResponse(): void
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
                ]
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

    /** @test */
    public function undProduziertFehler405MethodNotAllowed(): void
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
                ]
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
            'Leider angefragte HTTP Method "GET" nicht erlaubt. Erlaubt sind "POST".'
        );

        $app->handleRequest($request);
    }

    /** @test */
    public function undProduziertFehler404NotFound(): void
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
        $this->expectExceptionMessage('Leider angefragte Resource "/" nicht existent!');

        $app->handleRequest($request);
    }

    /** @test */
    public function undProduziert500UnbekannterServerFehler(): void
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
        $this->expectExceptionMessage('Unbekannter Server Fehler, Router Problem');

        $app->handleRequest($request);
    }
}
