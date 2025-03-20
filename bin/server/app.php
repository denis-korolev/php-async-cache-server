<?php

/**
 * @var $server \Amp\Http\Server\SocketHttpServer
 * @var $router \Amp\Http\Server\Router
 * @var $errorHandler \Amp\Http\Server\DefaultErrorHandler
 * @var $logger Monolog\Logger
 * @var $container \DI\Container
 */
[$server, $router, $errorHandler, $logger, $container] = require "init.php";

use function Amp\trapSignal;

$server->start($router, $errorHandler);

// вот это место заставляет ожидать команды прерывания и луп начинает работать
// Await SIGINT or SIGTERM to be received.
$signal = trapSignal([SIGINT, SIGTERM]);

$logger->info("Caught signal $signal, stopping server");

$server->stop();
