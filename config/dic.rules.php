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
use Waglpz\Webapp\Security\Firewalled;
use Waglpz\Webapp\UI\Cli\DbMigrations;
use Waglpz\Webapp\UI\Cli\DbReset;
use Waglpz\Webapp\UI\Http\Web\SwaggerUI;

use function FastRoute\simpleDispatcher;
use function Waglpz\Webapp\config;
use function Waglpz\Webapp\projectRoot;

return [
    '*'                              => [
        'substitutions' => [
            ExtendedPdoInterface::class      => '$DefaultPDO',
            Dispatcher::class                => [
                Dice::INSTANCE => static function (): Dispatcher {
                    return simpleDispatcher(config('router'));
                },
            ],
            PhpRenderer::class               => '$DefaultViewRenderer',
            EmitterInterface::class          => Emitter::class,
            LoggerInterface::class           => '$DefaultLogger',
            Firewalled::class                => null,
            ExceptionHandlerInvokable::class => '$DefaultExceptionHandler',
        ],
    ],
    DbMigrations::class              => [
        'shared'          => true,
        'constructParams' => [
            (include projectRoot() . '/cli.php')['commands']['db:migrations']['options'],
        ],
    ],
    DbReset::class              => [
        'shared'          => true,
        'constructParams' => [
            (include projectRoot() . '/cli.php')['commands']['db:migrations']['options'],
        ],
    ],
    '$DefaultExceptionHandler' => [
        'shared'          => true,
        'instanceOf'      => ExceptionHandler::class,
        'constructParams' => [config('logErrorsDir')],
    ],
    ServerRequestInterface::class    => [
        'shared'     => true,
        'instanceOf' => ServerRequestFactory::class,
        'call'       => [['createServerRequestFromGlobals', [], Dice::CHAIN_CALL]],
    ],
    '$DefaultViewRenderer'           => [
        'shared'          => true,
        'instanceOf'      => PhpRenderer::class,
        'constructParams' => [
            config('view')['templates'],
            config('view')['attributes'],
            config('view')['layout'],
        ],
    ],
    '$DefaultPDO'                    => [
        'shared'          => true,
        'instanceOf'      => ExtendedPdo::class,
        'constructParams' => [
            config('db')['dsn'],
            config('db')['username'],
            config('db')['password'],
            config('db')['options'] ?? null,
            config('db')['queries'] ?? null,
            config('db')['profiler'] ?? null,
        ],
    ],
    '$DefaultLogger'                 => [
        'shared'     => true,
        'instanceOf' => LoggerFactory::class,
        'call'       => [['create', ['default', config('logger')['default']], Dice::CHAIN_CALL]],
    ],
    // Controllers with specific params
    SwaggerUI::class                 => [
        'shared'          => true,
        'constructParams' => [config('swagger_scheme_file')],
    ],
];
