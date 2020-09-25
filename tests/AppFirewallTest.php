<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests;

use Aidphp\Http\Response;
use FastRoute\RouteCollector;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Waglpz\Webapp\App;
use Waglpz\Webapp\ExceptionHandler;
use Waglpz\Webapp\Security\Firewalled;

final class AppFirewallTest extends TestCase
{
    /** @var array<mixed> */
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = [
            'router'            => static fn (RouteCollector $router) => $router->get(
                '/',
                \get_class(
                    new class () {
                        public function __invoke(): ResponseInterface
                        {
                            return new Response();
                        }
                    }
                )
            ),
            'view'              => [
                'view_helper_factory' => \stdClass::class,
                'layout'              => '',
                'templates'           => '',
                'attributes'          => [],
            ],
            'viewHelpers'       => [],
            'firewall'          => [
                '/' => ['UNBEKANNT'],
            ],
            'exception_handler' => new ExceptionHandler(),
        ];
    }

    /** @test */
    public function enabledFirewallChecksRules(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getMethod')->willReturn('GET');
        $request->expects(self::once())->method('getRequestTarget')->willReturn('/');
        $firewall = $this->createMock(Firewalled::class);
        $firewall->expects(self::once())
                 ->method('checkRules')
                 ->with($request);
        (new App(
            $this->config,
            null,
            null,
            null,
            $firewall
        ))->run($request);
    }
}
