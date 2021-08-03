<?php

declare(strict_types=1);

//\Locale::setDefault('de_DE.utf8');

return [
    'apiVersion'          => '0.1.0',
    'logErrorsDir'        => '/tmp',
    'router'              => include 'router.php',
    'db'                  => [
        'dsn' => 'localhost',
        'username' => 'username',
        'password' => 'password',
    ],
    'logger'              => include 'logger.php',
    'view'                => [
        'templates'  => \dirname(__DIR__) . '/templates/',
        'attributes' => ['webseitenTitle' => 'Testseite'],
        'layout'     => 'layout.phtml',
    ],
    // uncomment to enable exception handler
    //'exception_handler'   => ExceptionHandler::class,
    // uncomment to enable firewall
    //'firewall'            => include 'firewall.php',
    'swagger_scheme_file' => __DIR__ . '/../swagger.json',
];
