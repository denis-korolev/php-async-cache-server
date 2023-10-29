<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Amp\ByteStream;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Socket\InternetAddress;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;

require dirname(__DIR__) . "/vendor/autoload.php";

$containerBuilder = new ContainerBuilder();
// on production
//$containerBuilder->enableCompilation(dirname(__DIR__) . '/var/cache');

$container = $containerBuilder->build();

$logHandler = new StreamHandler(ByteStream\getStdout());
$logHandler->pushProcessor(new PsrLogMessageProcessor());
$logHandler->setFormatter(new ConsoleFormatter());

$logger = new Logger('server');
$logger->pushHandler($logHandler);

$server = SocketHttpServer::createForDirectAccess($logger);

$server->expose(new InternetAddress("0.0.0.0", 80));
$server->expose(new InternetAddress("[::]", 80));

$errorHandler = new DefaultErrorHandler();

$router = new Router($server, $logger, $errorHandler);

$routes = require "routes.php";

foreach ($routes as $i => $route) {
    $router->addRoute(
        $route['method'],
        $route['uri'],
        $container->get($route['action'])
    );
}



return [$server,$router, $errorHandler, $logger, $container];
