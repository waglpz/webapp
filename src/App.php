<?php

declare(strict_types=1);

namespace Waglpz\Webapp;

use FastRoute\Dispatcher;
use Interop\Http\EmitterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Waglpz\Webapp\Security\Firewalled;

final class App
{
    private Dispatcher $dispatcher;
    private EmitterInterface $emitter;
    private Firewalled $firewall;
    private ContainerInterface $container;

    public function __construct(
        Dispatcher $dispatcher,
        EmitterInterface $emitter,
        ?Firewalled $firewall = null,
        ?ExceptionHandlerInvokable $exceptionHandler = null
    ) {
        $this->emitter    = $emitter;
        $this->dispatcher = $dispatcher;

        if ($firewall !== null) {
            $this->firewall = $firewall;
        }

        if ($exceptionHandler === null) {
            return;
        }

        // php stan prüfung akzeptiert diese block nur wenn anonyme function boolean returned
        \set_error_handler(
            static function ($errorCode, string $errorMessage): bool {
                throw new \Error($errorMessage, 500);
            }
        );

        \set_exception_handler($exceptionHandler);
    }

    public function run(ServerRequestInterface $request): void
    {
        $handler = $this->handleRequest($request);
        if (isset($this->firewall)) {
            $this->firewall->checkRules($request);
        }

        $response = $handler();
        $this->emitter->emit($response);
    }

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function handleRequest(ServerRequestInterface $request): \Closure
    {
        $httpMethod = $request->getMethod();
        $uri        = $request->getRequestTarget();
        $position   = \strpos($uri, '?');
        if ($position !== false) {
            $uri = \substr($uri, 0, $position);
        }

        $uri       = \rawurldecode($uri);
        $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);

        if (\count($routeInfo) === 3) {
            [0 => $info, 1 => $handler, 2 => $vars] = $routeInfo;
            if ($info === Dispatcher::FOUND) {
                if ($vars !== null) {
                    foreach ($vars as $name => $value) {
                        $request = $request->withAttribute($name, $value);
                    }
                }

                $controller = ($this->container ?? \Waglpz\Webapp\container())->get($handler);

                \assert(\is_callable($controller));

                return static fn () => ($controller)($request);
            }
        } elseif (\count($routeInfo) === 2) {
            [0 => $info, 1 => $allowedMethods] = $routeInfo;

            if ($info === Dispatcher::METHOD_NOT_ALLOWED) {
                $message = \sprintf(
                    'Leider angefragte HTTP Method "%s" nicht erlaubt. Erlaubt sind "%s".',
                    $httpMethod,
                    \implode(',', $allowedMethods)
                );

                throw new \Error($message, 405);
            }
        } elseif (\count($routeInfo) === 1 && $routeInfo[0] === Dispatcher::NOT_FOUND) {
            throw new \Error(\sprintf('Leider angefragte Resource "%s" nicht existent!', $uri), 404);
        }

        throw new \Error('Unbekannter Server Fehler, Router Problem', 500);
    }
}
