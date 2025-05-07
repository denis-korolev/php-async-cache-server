<?php

use Amp\Http\Server\Router;
use DI\Container;

function loadRoutes(array $routes, Router $router, Container $container): void
{
    foreach ($routes as $i => $route) {
        $router->addRoute(
            $route['method'],
            $route['uri'],
            $container->get($route['action'])
        );
    }
}
