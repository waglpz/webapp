<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

\date_default_timezone_set('Europe/Berlin');

require __DIR__ . '/../vendor/autoload.php';

const PROJECT_CONFIG_DIRECTORY = __DIR__ . '/../config';

(new Dotenv())->bootEnv(__DIR__ . '/../.env');

/* phpcs:disable */
if (! \defined('APP_ENV')) {
    \define('APP_ENV', $_SERVER['APP_ENV'] ?? 'dev');
}
/* phpcs:enable */


//@Todo: move next to response in base controller
\header('Access-Control-Allow-Origin: *');
\header('Access-Control-Allow-Headers: *');
\header('Access-Control-Allow-Methods: *');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

/** @phpstan-ignore-next-line */
\Waglpz\DiContainer\container()->get('$DefaultWebApp')->run(
    \Waglpz\DiContainer\container()->get(\Psr\Http\Message\ServerRequestInterface::class),
);
