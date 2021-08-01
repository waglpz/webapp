<?php

declare(strict_types=1);

use Monolog\Handler\SyslogHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [
    'default' => [
        'handlers'   => [
            [
                'name'         => SyslogHandler::class,
                'params'       => [
                    'ident'    => 'webapp-default-logger',
                    'facility' => 'local0',
                    'level'    => Psr\Log\LogLevel::DEBUG,
                ],
            ],
        ],
        'processors' => [
            [
                'name' => PsrLogMessageProcessor::class,
            ],
        ],
    ],
];
