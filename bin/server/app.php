<?php

/**
 * @var $server SocketHttpServer
 * @var $router Router
 * @var $errorHandler DefaultErrorHandler
 * @var $logger Monolog\Logger
 * @var $container Container
 */
[$server, $routes, $router, $errorHandler, $logger, $container] = require "init.php";

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\InternetAddress;
use DI\Container;
use PhpCache\gateway\GatewayClient;
use PhpCache\IpHelper;
use function Amp\trapSignal;

loadRoutes($routes, $router, $container);

$port = IpHelper::getPort();
$ip = IpHelper::getIP();
$address = $ip . ':' . $port;

$server->expose(new InternetAddress("[::]", $port));
$server->expose(new InternetAddress("0.0.0.0", $port));

$gatewayClient = $container->get(GatewayClient::class);

$server->onStart(
    function () use ($gatewayClient, $address, $logger) {

        Amp\async(
            function () use ($gatewayClient, $address, $logger) {
                $logger->info('Пытаемся зарегистрироваться в шлюзе');

                $maxRetries = 5;
                $retryDelay = 2; // секунды

                for ($i = 0; $i < $maxRetries; $i++) {
                    if ($gatewayClient->register($address)) {
                        $logger->info('Successfully registered with gateway');
                        break;
                    }

                    if ($i < $maxRetries - 1) {
                        $logger->warning("Failed to register with gateway, retrying in {$retryDelay} seconds...");
                        sleep($retryDelay);
                    } else {
                        $logger->error('Failed to register with gateway after ' . $maxRetries . ' attempts');
//                        throw new Exception('Failed to register with gateway after ' . $maxRetries . ' attempts');
                    }
                }

            // Регистрируем обработчик для корректного отключения от шлюза при завершении работы
                register_shutdown_function(
                    function () use ($gatewayClient, $logger, $address) {
                        $gatewayClient->unregister($address);
                        $logger->info('Unregistered from gateway');
                    }
                );
            }
        );
    }
);

$server->onStop(
    function () use ($gatewayClient, $address, $logger) {
        $gatewayClient->unregister($address);
        $logger->info('Unregistered from gateway');
    }
);

$server->start($router, $errorHandler);

// вот это место заставляет ожидать команды прерывания и луп начинает работать
// Await SIGINT or SIGTERM to be received.
$signal = trapSignal([SIGINT, SIGTERM]);

$logger->info("Caught signal $signal, stopping server");

$server->stop();
