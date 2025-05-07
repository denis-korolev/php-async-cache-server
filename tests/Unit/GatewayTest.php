<?php

declare(strict_types=1);

namespace Test\Unit;

use Amp\ByteStream\Payload;
use Amp\Http\Client\Response;
use Amp\Http\HttpStatus;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use DI\Container;
use League\Uri\Http;
use PhpCache\gateway\services\ExternalHttpRequest;
use PhpCache\gateway\services\ProxyRequestService;
use PHPUnit\Framework\TestCase;

class GatewayTest extends TestCase
{
    private Router $router;
    private Request $request;
    private Container $diContainer;
    private DefaultErrorHandler $errorHandler;
    private SocketHttpServer $server;
    private array $routes;
    private string $testServerUrl = 'http://localhost:8080';

    public function setUp(): void
    {
        /**
         * @var $server \Amp\Http\Server\SocketHttpServer
         * @var $routes array
         * @var $router \Amp\Http\Server\Router
         * @var $errorHandler \Amp\Http\Server\DefaultErrorHandler
         * @var $logger \Monolog\Logger
         * @var $container \DI\Container
         */
        [$server, $routes, $router, $errorHandler, $logger, $container] = require dirname(__DIR__, 2) . '/bin/gateway/init.php';

        $this->routes = $routes;
        $this->errorHandler = $errorHandler;
        $this->diContainer = $container;
        $this->router = $router;
        $this->server = $server;
        $this->request = new Request(
            $this->createMock(Client::class),
            'GET',
            Http::new('/')
        );
    }

    public function tearDown(): void
    {
        $this->server->stop();
    }

    public function testGatewayInitialState(): void
    {
        loadRoutes($this->routes, $this->router, $this->diContainer);
        $this->server->start($this->router, $this->errorHandler);
        // Проверяем, что изначально нет зарегистрированных серверов
        $payload = new Payload($this->router->handleRequest($this->request)->getBody());
        self::assertEquals('[]', $payload->buffer());
    }

    public function testManualServerRegistration(): void
    {
        loadRoutes($this->routes, $this->router, $this->diContainer);
        $this->server->start($this->router, $this->errorHandler);

        // Регистрируем сервер вручную
        $this->request->setMethod('PUT');
        $this->request->setUri(Http::new('/register'));
        $this->request->setBody(json_encode(['ip' => $this->testServerUrl]));

        $response = $this->router->handleRequest($this->request);
        self::assertEquals(HttpStatus::OK, $response->getStatus());

        // Проверяем, что сервер появился в списке
        $this->request->setMethod('GET');
        $this->request->setUri(Http::new('/'));
        $response = $this->router->handleRequest($this->request);
        $payload = new Payload($response->getBody());

        $servers = json_decode($payload->buffer(), true);
        self::assertArrayHasKey($this->testServerUrl, $servers);
    }

    public function testAutoServerRegistration(): void
    {
        $httpClient = $this->getMockBuilder(ExternalHttpRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $httpClient->method('checkClient')->willReturn(200);

        $this->diContainer->set(ExternalHttpRequest::class, $httpClient);

        loadRoutes($this->routes, $this->router, $this->diContainer);
        $this->server->start($this->router, $this->errorHandler);

//        Надо заранее подставить DI в инициализацию

        // Регистрируем сервер через авторегистрацию
        $this->request->setMethod('POST');
        $this->request->setUri(Http::new('/auto-register'));
        $this->request->setBody(json_encode(['ip' => $this->testServerUrl]));

        $response = $this->router->handleRequest($this->request);
        self::assertEquals(HttpStatus::OK, $response->getStatus());

        // Проверяем ответ авторегистрации
        $payload = new Payload($response->getBody());
        $result = json_decode($payload->buffer(), true);

        self::assertEquals('success', $result['status']);
        self::assertEquals($this->testServerUrl, $result['server']['ip']);
        self::assertEquals('active', $result['server']['status']);
    }

    public function testServerUnregistration(): void
    {
        loadRoutes($this->routes, $this->router, $this->diContainer);
        $this->server->start($this->router, $this->errorHandler);

        // Сначала регистрируем сервер
        $this->request->setMethod('PUT');
        $this->request->setUri(Http::new('/register'));
        $this->request->setBody(json_encode(['ip' => $this->testServerUrl]));
        $this->router->handleRequest($this->request);

        // Удаляем сервер
        $this->request->setMethod('DELETE');
        $this->request->setUri(Http::new('/register'));
        $this->request->setBody(json_encode(['ip' => $this->testServerUrl]));

        $response = $this->router->handleRequest($this->request);
        self::assertEquals(HttpStatus::OK, $response->getStatus());

        // Проверяем, что сервер удален из списка
        $this->request->setMethod('GET');
        $this->request->setUri(Http::new('/'));
        $response = $this->router->handleRequest($this->request);
        $payload = new Payload($response->getBody());

        $servers = json_decode($payload->buffer(), true);
        self::assertArrayNotHasKey($this->testServerUrl, $servers);
    }

    public function testProxyRequests(): void
    {
        $httpClient = $this->getMockBuilder(ProxyRequestService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $httpClient->method('makeRequest')->willReturnOnConsecutiveCalls(
            new Response(
                '2',
                HttpStatus::OK,
                null,
                ['content-type' => 'text/plain'],
                '',
                new \Amp\Http\Client\Request(''),
            ),
            new Response(
                '2',
                HttpStatus::OK,
                null,
                ['content-type' => 'text/plain'],
                'test-value',
                new \Amp\Http\Client\Request(''),
            ),
            // DELETE
            new Response(
                '2',
                HttpStatus::OK,
                null,
                ['content-type' => 'text/plain'],
                '',
                new \Amp\Http\Client\Request(''),
            ),
            // GET
            new Response(
                '2',
                HttpStatus::NOT_FOUND,
                null,
                ['content-type' => 'text/plain'],
                '',
                new \Amp\Http\Client\Request(''),
            ),
        );

        $this->diContainer->set(ProxyRequestService::class, $httpClient);


        loadRoutes($this->routes, $this->router, $this->diContainer);
        $this->server->start($this->router, $this->errorHandler);

        // Регистрируем тестовый сервер
        $this->request->setMethod('PUT');
        $this->request->setUri(Http::new('/register'));
        $this->request->setBody(json_encode(['ip' => $this->testServerUrl]));
        $this->router->handleRequest($this->request);

        // Тестируем PUT запрос
        $this->request->setMethod('PUT');
        $this->request->setUri(Http::new('/test-key'));
        $this->request->setBody('test-value');

        $response = $this->router->handleRequest($this->request);
        self::assertEquals(HttpStatus::OK, $response->getStatus());

        // Тестируем GET запрос
        $this->request = new Request(
            $this->createMock(Client::class),
            'GET',
            Http::new('/test-key')
        );

        $response = $this->router->handleRequest($this->request);
        self::assertEquals(HttpStatus::OK, $response->getStatus());
        self::assertEquals('test-value', $response->getBody()->read());

        // Тестируем DELETE запрос
        $this->request = new Request(
            $this->createMock(Client::class),
            'DELETE',
            Http::new('/test-key')
        );
        $response = $this->router->handleRequest($this->request);
        self::assertEquals(HttpStatus::OK, $response->getStatus());

        // Проверяем, что значение удалено
        $this->request = new Request(
            $this->createMock(Client::class),
            'GET',
            Http::new('/test-key')
        );
        $this->request->setMethod('GET');
        $this->request->setUri(Http::new('/test-key'));

        $response = $this->router->handleRequest($this->request);
        self::assertEquals(HttpStatus::NOT_FOUND, $response->getStatus());
    }

    public function testLoadBalancing(): void
    {
        loadRoutes($this->routes, $this->router, $this->diContainer);
        $this->server->start($this->router, $this->errorHandler);

        // Регистрируем два тестовых сервера
        $server1 = 'http://localhost:8081';
        $server2 = 'http://localhost:8082';

        $this->request->setMethod('PUT');
        $this->request->setUri(Http::new('/register'));
        $this->request->setBody(json_encode(['ip' => $server1]));
        $this->router->handleRequest($this->request);

        $this->request->setMethod('PUT');
        $this->request->setUri(Http::new('/register'));
        $this->request->setBody(json_encode(['ip' => $server2]));
        $this->router->handleRequest($this->request);

        // Проверяем, что оба сервера зарегистрированы
        $this->request->setMethod('GET');
        $this->request->setUri(Http::new('/'));
        $response = $this->router->handleRequest($this->request);
        $payload = new Payload($response->getBody());

        $servers = json_decode($payload->buffer(), true);
        self::assertArrayHasKey($server1, $servers);
        self::assertArrayHasKey($server2, $servers);
    }

    public function testInvalidRegistration(): void
    {
        loadRoutes($this->routes, $this->router, $this->diContainer);
        $this->server->start($this->router, $this->errorHandler);
        // Пытаемся зарегистрировать сервер без IP
        $this->request->setMethod('POST');
        $this->request->setUri(Http::new('/auto-register'));
        $this->request->setBody(json_encode([]));

        $response = $this->router->handleRequest($this->request);
        self::assertEquals(HttpStatus::BAD_REQUEST, $response->getStatus());

        $payload = new Payload($response->getBody());
        $result = json_decode($payload->buffer(), true);
        self::assertEquals('IP address is required', $result['error']);
    }
}
