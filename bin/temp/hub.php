<?php

declare(strict_types=1);

require dirname(__DIR__) . "/vendor/autoload.php";


$address = 'tcp://127.0.0.2:10001';

$server = new \PhpCache\HubServer(['socket' => $address]);
$server->start();