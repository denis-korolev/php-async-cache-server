<?php

declare(strict_types=1);

namespace Test\Unit;

use Amp\ByteStream\Payload;
use Amp\Http\HttpStatus;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\Router;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

class GatewayTest extends TestCase
{
    private Router $router;
    private Request $request;

    public function setUp(): void
    {
        /**
         * @var $server \Amp\Http\Server\SocketHttpServer
         * @var $router \Amp\Http\Server\Router
         * @var $errorHandler \Amp\Http\Server\DefaultErrorHandler
         * @var $logger \Monolog\Logger
         * @var $container \DI\Container
         */
        [$server, $router, $errorHandler, $logger, $container] = require dirname(__DIR__, 2) . '/bin/gateway/init.php';

        $this->router = $router;
        $this->request = new Request(
            $this->createMock(Client::class),
            'GET',
            Http::new('/')
        );
        $server->start($router, $errorHandler);
    }

    public function testGateway(): void
    {
        // проверяем, что ничего нет в кеше
        $payload = new Payload($this->router->handleRequest($this->request)->getBody());
        self::assertEquals('[]', $payload->buffer());

        // пишем в кеш
        $this->request->setMethod('PUT');
        $this->request->setUri(Http::new('/register'));

        $body = ['ip' => '192.168.12.12'];
        $this->request->setBody(
            json_encode(
                $body,
                JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );

        $response = $this->router->handleRequest($this->request);
        $payload = new Payload($response->getBody());
        self::assertEquals('', $payload->buffer());
        self::assertEquals(HttpStatus::OK, $response->getStatus());

        // проверяем ответ с выводом списка зарегистрированных серверов
        $this->request->setMethod('GET');
        $this->request->setUri(Http::new('/'));

        $response = $this->router->handleRequest($this->request);
        self::assertEquals(HttpStatus::OK, $response->getStatus());
        $payload = new Payload($response->getBody());
        self::assertEquals(
            '{"192.168.12.12":{"ip":"192.168.12.12","description":"Возможно тут будем заполнять что - то интересное, но потом"}}',
            $payload->buffer()
        );

        // регаемся еще одним сервером
        $this->request->setMethod('PUT');
        $this->request->setUri(Http::new('/register'));

        $body = ['ip' => '192.168.12.13'];
        $this->request->setBody(
            json_encode(
                $body,
                JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );
        $response = $this->router->handleRequest($this->request);
        self::assertEquals(HttpStatus::OK, $response->getStatus());


        // проверяем ответ с выводом списка зарегистрированных серверов
        $this->request->setMethod('GET');
        $this->request->setUri(Http::new('/'));

        $response = $this->router->handleRequest($this->request);
        self::assertEquals(HttpStatus::OK, $response->getStatus());
        $payload = new Payload($response->getBody());
        self::assertEquals(
            '{"192.168.12.12":{"ip":"192.168.12.12","description":"Возможно тут будем заполнять что - то интересное, но потом"},"192.168.12.13":{"ip":"192.168.12.13","description":"Возможно тут будем заполнять что - то интересное, но потом"}}',
            $payload->buffer()
        );

//        $this->request->setMethod('GET');
//        $this->request->setUri(Http::new('/key'));
//
//        $response = $this->router->handleRequest($this->request);
//        $payload = new Payload($response->getBody());
//        self::assertEquals(HttpStatus::OK, $response->getStatus());
//        self::assertEquals('{"body":"какое - то значение"}', $payload->buffer());
//
//        // delete
//        $this->request->setMethod('DELETE');
//        $this->request->setUri(Http::new('/key'));
//
//        $response = $this->router->handleRequest($this->request);
//        self::assertEquals(HttpStatus::OK, $response->getStatus());
//
//        $this->request->setMethod('GET');
//        $this->request->setUri(Http::new('/key'));
//
//        $response = $this->router->handleRequest($this->request);
//        self::assertEquals(HttpStatus::NOT_FOUND, $response->getStatus());
//
//        // проверяем вывод содержимого
//
//        $data = [
//            'www' => 1111,
//            'foo' => 'dfgdk',
//            'bar' => '["sdfwwwwww" => "sdfsdfxc2322"]',
//            'buzz' => 032423,
//            'kukuepta' => 'aga',
//        ];
//        $this->request->setMethod('PUT');
//        foreach ($data as $key => $value) {
//            $this->request->setUri(Http::new('/' . $key . '?ttl=5'));
//            $this->request->setBody((string)$value);
//            $response = $this->router->handleRequest($this->request);
//            self::assertEquals(HttpStatus::OK, $response->getStatus());
//        }
//
//
//        $this->request->setMethod('GET');
//        $this->request->setUri(Http::new('/'));
//        $payload = new Payload($this->router->handleRequest($this->request)->getBody());
//        self::assertEquals(
//            '{"www":"1111","foo":"dfgdk","bar":"[\"sdfwwwwww\" => \"sdfsdfxc2322\"]","buzz":"13587","kukuepta":"aga"}',
//            $payload->buffer()
//        );
    }
}
