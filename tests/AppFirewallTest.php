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
use Waglpz\Webapp\Security\Firewalled;

final class AppFirewallTest extends TestCase
{
    /** @test */
    public function enabledFirewallChecksRules(): void
    {
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with('GET', '/')
            ->willReturn(
                [
                    0 => Dispatcher::FOUND,
                    1 => 'handler',
                    2 => ['rp' => 'abc'],
                ]
            );

        $emitter = $this->createMock(EmitterInterface::class);
        $emitter->expects(self::once())->method('emit')->with(self::isInstanceOf(ResponseInterface::class));

        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->willReturn(static fn () => new \Aidphp\Http\Response());

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('GET');
        $request->expects(self::once())->method('getRequestTarget')->willReturn('/');
        $firewall = $this->createMock(Firewalled::class);
        $firewall->expects(self::once())->method('checkRules')->with($request);

        $app = new App($dispatcher, $emitter, $firewall);
        $app->setContainer($container);
        $app->run($request);
    }
}
