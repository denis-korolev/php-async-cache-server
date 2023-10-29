<?php

declare(strict_types=1);

use PhpCache\DeleteAction;
use PhpCache\GetAction;
use PhpCache\IndexAction;
use PhpCache\SetAction;

return [
    ['method' => 'GET', 'uri' => '/', 'action' => IndexAction::class],
    ['method' => 'GET', 'uri' => '/{key}', 'action' => GetAction::class],
    ['method' => 'PUT', 'uri' => '/{key}', 'action' => SetAction::class],
    ['method' => 'DELETE', 'uri' => '/{key}', 'action' => DeleteAction::class],
];
