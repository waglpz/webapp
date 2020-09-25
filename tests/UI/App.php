<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Tests\UI;

final class App
{
    public static function createApp(): \Waglpz\Webapp\App
    {
        $_SERVER['REQUEST_SCHEME'] = 'http';
        $_SERVER['HTTP_HOST']      = 'localhost';
        $_SESSION                  = [];
        $_COOKIE                   = [];
        $_REQUEST                  = [];

        $config = include \dirname(__DIR__) . '/../config/main.php';

        return new \Waglpz\Webapp\App($config);
    }
}
