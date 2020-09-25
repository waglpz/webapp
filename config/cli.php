<?php

declare(strict_types=1);

use Waglpz\Webapp\UI\Cli\DbMigrations;

\Locale::setDefault('de_DE.utf8');

$executor = isset($_ENV['COMPOSER_BINARY']) ? ' composer waglpz:cli ' : ' php ' . $_SERVER['argv'][0] . ' ';

return [
    'logErrorsDir' => '/tmp',
    // uncomment to enable exception handler
    //'exception_handler'   => Waglpz\Webapp\CliExceptionHandler::class,
    'commands'     => [
        'db:migrations' => [
            'options'  => [
                'usage'      => [
                    $executor . 'db:migrations generate',
                    $executor . 'db:migrations migrate',
                ],
                'migrations' => __DIR__ . '/../migrations',
            ],
            'executor' => DbMigrations::class,
        ],
        'generate:password'      => [],
    ],
];
