<?php

declare(strict_types=1);

use Waglpz\Webapp\UI\Cli\DbMigrations;

//\Locale::setDefault('de_DE.utf8');

return [
    'logErrorsDir' => '/tmp',
    // uncomment to enable exception handler
    //'exception_handler'   => Waglpz\Webapp\CliExceptionHandler::class,
    'commands'     => [
        'db:migrations' => [
            'options'  => [
                'usage'      => [
                    'php bin/cli db:migrations generate',
                    'php bin/cli db:migrations migrate',
                ],
                'migrations' => __DIR__ . '/../migrations',
            ],
            'executor' => DbMigrations::class,
        ],
        'generate:password'      => [],
    ],
];
