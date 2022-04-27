<?php

declare(strict_types=1);

use FastRoute\RouteCollector;
use Waglpz\Webapp\UI\Http\Rest\Ping;
use Waglpz\Webapp\UI\Http\Web\SwaggerUI;

return static function (RouteCollector $router): void {
    $router->addGroup(
        '/api',
        static function (RouteCollector $routeCollector): void {
            $routeCollector->get('/ping', Ping::class);
            $routeCollector->get('/doc', SwaggerUI::class);
            $routeCollector->get('/doc.json', SwaggerUI::class);
        }
    );
};
