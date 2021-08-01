<?php

declare(strict_types=1);

use Waglpz\Webapp\UI\Cli\DbMigrations;

use function Waglpz\Webapp\cliExecutorName;

\Locale::setDefault('de_DE.utf8');

return [
    'logErrorsDir' => '/tmp',
    // uncomment to enable exception handler
    //'exception_handler'   => Waglpz\Webapp\CliExceptionHandler::class,
    'commands'     => [
        'db:migrations' => [
            'options'  => [
                'usage'      => [
                    cliExecutorName() . 'db:migrations generate',
                    cliExecutorName() . 'db:migrations migrate',
                ],
                'migrations' => __DIR__ . '/../migrations',
            ],
            'executor' => DbMigrations::class,
        ],
    ],
];
