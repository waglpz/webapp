<?php

declare(strict_types=1);

\Locale::setDefault('de_DE.utf8');

return [
    'apiVersion'          => '0.1.0',
    'anonymizeLog' => [
        '_SERVER' => [
            'DB_PASSWD' => '*****',
            'DB_USER' => '*****',
        ],
        '_POST' => [/* set here necessary keys wich should be anonymized in log*/],
    ],
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
// To enable and using view helper component please
// install webapp-view-helpers via composer require waglpz/webapp-view-helpers
// after installing pls uncomment next block.
//    'viewHelpers'         => [
//        'dateFormat'     => DateFormatter::class,
//        'modalDialog'    => ModalDialog::class,
//        'sortingButtons' => SortingButtons::class,
//        'navigation'     => Navigation::class,
//        'tabs'           => Tabs::class,
//        'workflowLog'    => WorkflowLog::class,
//        'url'            => static fn (
//            string $route,
//            ?array $routeArguments = null,
//            ?array $queryParams = null,
//            int $retainHash = Url::RETAIN_HASH
//        ): string => (new Url(webBase()))($route, $routeArguments, $queryParams, $retainHash),
//        'pagination'     => Pagination::class,
//    ],
    // uncomment to enable exception handler
    //'exception_handler'   => ExceptionHandler::class,
    // install waglpz/webapp-security component and uncomment to enable firewall
    //'firewall'            => include 'firewall.php',
    'swagger_scheme_file' => __DIR__ . '/../swagger.json',
];
