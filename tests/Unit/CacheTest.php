<?php

declare(strict_types=1);

namespace Test\Unit;

use Amp\ByteStream\Payload;
use Amp\Http\HttpStatus;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\InternetAddress;
use DI\Container;
use League\Uri\Http;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    private Router $router;
    private array $routes;
    private Request $request;
    private SocketHttpServer $server;
    private DefaultErrorHandler $errorHandler;
    private Logger $logger;
    private Container $container;
    public function setUp(): void
    {

        /**
         * @var $server SocketHttpServer
         * @var $router Router
         * @var $routers array
         * @var $errorHandler DefaultErrorHandler
         * @var $logger Logger
         * @var $container Container
         */
        [$server, $routes, $router, $errorHandler, $logger, $container] = require dirname(__DIR__, 2) . '/bin/server/init.php';

        $this->request = new Request(
            $this->createMock(Client::class),
            'GET',
            Http::new('/')
        );

        $this->router = $router;
        $this->routes = $routes;
        $this->logger = $logger;
        $this->container = $container;
        $this->errorHandler = $errorHandler;
        $this->server = $server;
    }

    public function testServer(): void
    {
        loadRoutes($this->routes, $this->router, $this->container);

        $this->server->expose(new InternetAddress("[::]", 11));
        $this->server->expose(new InternetAddress("0.0.0.0", 11));
        $this->server->start($this->router, $this->errorHandler);

        // проверяем, что ничего нет в кеше
        $payload = new Payload($this->router->handleRequest($this->request)->getBody());
        self::assertEquals('[]', $payload->buffer());

        // пишем в кеш
        $this->request->setMethod('PUT');
        $this->request->setUri(Http::new('/key?ttl=5'));

        $body = ['body' => 'какое - то значение'];
        $this->request->setBody(json_encode($body, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $response = $this->router->handleRequest($this->request);
        $payload = new Payload($response->getBody());
        self::assertEquals('', $payload->buffer());
        self::assertEquals(HttpStatus::OK, $response->getStatus());

        // проверка, что по неизвестному ключу что - то решили получить
        $this->request->setMethod('GET');
        $this->request->setUri(Http::new('/key1'));

        $response = $this->router->handleRequest($this->request);
        self::assertEquals(HttpStatus::NOT_FOUND, $response->getStatus());

        $this->request->setMethod('GET');
        $this->request->setUri(Http::new('/key'));

        $response = $this->router->handleRequest($this->request);
        $payload = new Payload($response->getBody());
        self::assertEquals(HttpStatus::OK, $response->getStatus());
        self::assertEquals('{"body":"какое - то значение"}', $payload->buffer());

        // delete
        $this->request->setMethod('DELETE');
        $this->request->setUri(Http::new('/key'));

        $response = $this->router->handleRequest($this->request);
        self::assertEquals(HttpStatus::OK, $response->getStatus());

        $this->request->setMethod('GET');
        $this->request->setUri(Http::new('/key'));

        $response = $this->router->handleRequest($this->request);
        self::assertEquals(HttpStatus::NOT_FOUND, $response->getStatus());

        // проверяем вывод содержимого

        $data = [
            'www' => 1111,
            'foo' => 'dfgdk',
            'bar' => '["sdfwwwwww" => "sdfsdfxc2322"]',
            'buzz' => 032423,
            'kukuepta' => 'aga',
        ];
        $this->request->setMethod('PUT');
        foreach ($data as $key => $value) {
            $this->request->setUri(Http::new('/' . $key . '?ttl=5'));
            $this->request->setBody((string)$value);
            $response = $this->router->handleRequest($this->request);
            self::assertEquals(HttpStatus::OK, $response->getStatus());
        }


        $this->request->setMethod('GET');
        $this->request->setUri(Http::new('/'));
        $payload = new Payload($this->router->handleRequest($this->request)->getBody());
        self::assertEquals(
            '{"www":"1111","foo":"dfgdk","bar":"[\"sdfwwwwww\" => \"sdfsdfxc2322\"]","buzz":"13587","kukuepta":"aga"}',
            $payload->buffer()
        );
    }
}
