<?php

declare(strict_types=1);

namespace PhpCache\gateway;

use Amp\Cache\LocalCache;
use Amp\Http\Client\Request as ClientRequest;
use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use PhpCache\gateway\services\ProxyRequestService;

final class ProxyAction implements RequestHandler
{
    public function __construct(
        private LocalCache $cache,
        private ProxyRequestService $proxyRequestService
    ) {
    }

    public function handleRequest(Request $request): Response
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();
        $body = $request->getBody()->buffer();

        // Получаем список зарегистрированных серверов
        $registeredList = $this->cache->get('registered');
        if ($registeredList === null) {
            return new Response(
                HttpStatus::SERVICE_UNAVAILABLE,
                ['content-type' => 'application/json'],
                json_encode(['error' => 'No cache servers available'])
            );
        }

        $servers = json_decode($registeredList, true);
        if (empty($servers)) {
            return new Response(
                HttpStatus::SERVICE_UNAVAILABLE,
                ['content-type' => 'application/json'],
                json_encode(['error' => 'No cache servers available'])
            );
        }

        // Выбираем сервер на основе хеша ключа для равномерного распределения
        $key = trim($path, '/');
        $serverIndex = crc32($key) % count($servers);
        $selectedServer = array_values($servers)[$serverIndex];

        // Формируем URL для запроса к выбранному серверу
        $serverUrl = "{$selectedServer['ip']}{$path}";

        try {
            // Создаем запрос к выбранному серверу
            $clientRequest = new ClientRequest($serverUrl, $method);
            if (!empty($body)) {
                $clientRequest->setBody($body);
            }

            // Выполняем запрос
            $response = $this->proxyRequestService->makeRequest($clientRequest);
            $responseBody = $response->getBody()->buffer();

            // Возвращаем ответ от сервера
            return new Response(
                $response->getStatus(),
                $response->getHeaders(),
                $responseBody
            );
        } catch (\Exception $e) {
            // todo логирование текста эксепшена
            return new Response(
                HttpStatus::BAD_GATEWAY,
                ['content-type' => 'application/json'],
                json_encode(['error' => 'Failed to proxy request to cache server'])
            );
        }
    }
}
