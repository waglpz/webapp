<?php

declare(strict_types=1);

use Waglpz\Webapp\App;

/* phpcs:disable */
if (! \defined('APP_ENV')) {
    \define('APP_ENV', \getenv('APP_ENV') ?? 'dev');
}
/* phpcs:enable */

if (is_file(__DIR__ . '/../autoload.php') === true) {
    include_once __DIR__ . '/../autoload.php';
} else {
    include_once __DIR__ . '/../vendor/autoload.php';
}

$_SERVER['REQUEST_SCHEME'] = '';
$_SERVER['HTTP_HOST']      = '';

$config = include __DIR__ . '/../config/main.php';

return new App($config);
