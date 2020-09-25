<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests;

use FastRoute\Dispatcher;
use Interop\Http\EmitterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\PhpRenderer;
use Waglpz\Webapp\App;
use Waglpz\Webapp\ExceptionHandler;

final class AppErrorHandleTest extends TestCase
{
    /** @test */
    public function undConvertInEineException(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Test Error');

        $config     = [
            'router'            => static fn () => null,
            'view'              => [
                'view_helper_factory' => \stdClass::class,
                'layout'              => '',
                'templates'           => '',
                'attributes'          => [],
            ],
            'viewHelpers'       => [],
            'exception_handler' => ExceptionHandler::class,
        ];
        $view       = $this->createMock(PhpRenderer::class);
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects(self::never())->method('dispatch');
        $emitter = $this->createMock(EmitterInterface::class);
        (new App($config, $dispatcher, $view, $emitter));
        \trigger_error('Test Error');
    }

    /** @test */
    public function undProduziertFehlerWennExceptionHandlerVomFalscherType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Ungültige Exception Handler Class, erwartet "%s"',
                ExceptionHandler::class
            )
        );

        $config     = [
            'router'            => static fn () => null,
            'view'              => [
                'view_helper_factory' => \stdClass::class,
                'layout'              => '',
                'templates'           => '',
                'attributes'          => [],
            ],
            'viewHelpers'       => [],
            'exception_handler' => \stdClass::class,
        ];
        $view       = $this->createMock(PhpRenderer::class);
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects(self::never())->method('dispatch');
        $emitter = $this->createMock(EmitterInterface::class);
        (new App($config, $dispatcher, $view, $emitter));
    }

    /** @test */
    public function undExceptionHandlerEmmitErrorResponse(): void
    {
        $exceptionHandler = new ExceptionHandler();

        $exception = $this->createMock(\Throwable::class);
        $emitter   = $this->createMock(EmitterInterface::class);
        $emitter->expects(self::once())->method('emit')->with(self::isInstanceOf(ResponseInterface::class));
        $exceptionHandler($exception, $emitter);
        $exceptionHandler($exception);
    }
}
