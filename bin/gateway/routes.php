<?php

declare(strict_types=1);

use PhpCache\gateway\IndexAction;
use PhpCache\gateway\RegisterAction;
use PhpCache\gateway\UnregisterAction;

return [
    ['method' => 'GET', 'uri' => '/', 'action' => IndexAction::class],
    ['method' => 'PUT', 'uri' => '/register', 'action' => RegisterAction::class],
    ['method' => 'DELETE', 'uri' => '/register', 'action' => UnregisterAction::class],
];
