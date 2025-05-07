<?php

declare(strict_types=1);

use PhpCache\gateway\IndexAction;
use PhpCache\gateway\RegisterAction;
use PhpCache\gateway\UnregisterAction;
use PhpCache\gateway\ProxyAction;
use PhpCache\gateway\AutoRegisterAction;

return [
    ['method' => 'GET', 'uri' => '/', 'action' => IndexAction::class],
    ['method' => 'PUT', 'uri' => '/register', 'action' => RegisterAction::class],
    ['method' => 'DELETE', 'uri' => '/register', 'action' => UnregisterAction::class],
    ['method' => 'POST', 'uri' => '/auto-register', 'action' => AutoRegisterAction::class],
    // Маршруты для проксирования запросов к кеш-серверам
    ['method' => 'GET', 'uri' => '/{key}', 'action' => ProxyAction::class],
    ['method' => 'PUT', 'uri' => '/{key}', 'action' => ProxyAction::class],
    ['method' => 'DELETE', 'uri' => '/{key}', 'action' => ProxyAction::class],
];
