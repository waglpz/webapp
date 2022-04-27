<?php

declare(strict_types=1);

use Aidphp\Http\Emitter;
use Aidphp\Http\ServerRequestFactory;
use Aura\Sql\ExtendedPdo;
use Aura\Sql\ExtendedPdoInterface;
use Dice\Dice;
use FastRoute\Dispatcher;
use Interop\Http\EmitterInterface;
use MonologFactory\LoggerFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Views\PhpRenderer;
use Waglpz\Webapp\ExceptionHandler;
use Waglpz\Webapp\ExceptionHandlerInvokable;
use Waglpz\Webapp\UI\Cli\DbMigrations;
use Waglpz\Webapp\UI\Cli\DbReset;
use Waglpz\Webapp\UI\Http\Web\SwaggerUI;

use function FastRoute\simpleDispatcher;
use function Waglpz\Webapp\config;
use function Waglpz\Webapp\projectRoot;

return [
    '*'                           => [
        'substitutions' => [
            ExtendedPdoInterface::class      => '$DefaultPDO',
            Dispatcher::class                => [
                Dice::INSTANCE => static function (): Dispatcher {
                    $routerCollector = config('router');
                    \assert(\is_callable($routerCollector));

                    return simpleDispatcher($routerCollector);
                },
            ],
            PhpRenderer::class               => '$DefaultViewRenderer',
            EmitterInterface::class          => Emitter::class,
            LoggerInterface::class           => '$DefaultLogger',
            ExceptionHandlerInvokable::class => '$DefaultExceptionHandler',
        ],
    ],
    DbMigrations::class           => [
        'shared'          => true,
        'constructParams' => [
            (include projectRoot() . '/cli.php')['commands']['db:migrations']['options'],
        ],
    ],
    DbReset::class                => [
        'shared'          => true,
        'constructParams' => [
            (include projectRoot() . '/cli.php')['commands']['db:migrations']['options'],
        ],
    ],
    '$DefaultExceptionHandler'    => [
        'shared'          => true,
        'instanceOf'      => ExceptionHandler::class,
        'constructParams' => [config('logErrorsDir'), config('anonymizeLog')],
    ],
    ServerRequestInterface::class => [
        'shared'     => true,
        'instanceOf' => ServerRequestFactory::class,
        'call'       => [['createServerRequestFromGlobals', [], Dice::CHAIN_CALL]],
    ],
    '$DefaultViewRenderer'        => [
        'shared'          => true,
        'instanceOf'      => PhpRenderer::class,
        'constructParams' => [
            /** @phpstan-ignore-next-line */
            config('view')['templates'],
            /** @phpstan-ignore-next-line */
            config('view')['attributes'],
            /** @phpstan-ignore-next-line */
            config('view')['layout'],
        ],
    ],
    '$DefaultPDO'                 => [
        'shared'          => true,
        'instanceOf'      => ExtendedPdo::class,
        'constructParams' => [
            /** @phpstan-ignore-next-line */
            config('db')['dsn'],
            /** @phpstan-ignore-next-line */
            config('db')['username'],
            /** @phpstan-ignore-next-line */
            config('db')['password'],
            /** @phpstan-ignore-next-line */
            config('db')['options'] ?? null,
            /** @phpstan-ignore-next-line */
            config('db')['queries'] ?? null,
            /** @phpstan-ignore-next-line */
            config('db')['profiler'] ?? null,
        ],
    ],
    '$DefaultLogger'              => [
        'shared'     => true,
        'instanceOf' => LoggerFactory::class,
        'call'       => [
            [
                'create',
                [
                    'default',
                    /** @phpstan-ignore-next-line */
                    config('logger')['default'],
                ],
                Dice::CHAIN_CALL,
            ],
        ],
    ],
    // Controllers with specific params
    SwaggerUI::class              => [
        'shared'          => true,
        'constructParams' => [config('swagger_scheme_file')],
    ],
];
