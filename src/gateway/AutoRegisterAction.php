<?php

declare(strict_types=1);

namespace PhpCache\gateway;

use Amp\Cache\LocalCache;
use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use PhpCache\gateway\services\ExternalHttpRequest;

final class AutoRegisterAction implements RequestHandler
{
    public function __construct(
        private LocalCache $cache,
        private ExternalHttpRequest $externalHttpRequest
    ) {
    }

    public function handleRequest(Request $request): Response
    {
        $body = json_decode($request->getBody()->buffer(), true);

        if (!isset($body['ip'])) {
            return new Response(
                HttpStatus::BAD_REQUEST,
                ['content-type' => 'application/json'],
                json_encode(['error' => 'IP address is required'])
            );
        }

        $ip = (string)$body['ip'];

        // Проверяем доступность сервера
        try {
            $status = $this->externalHttpRequest->checkClient("http://{$ip}/");

            if ($status !== HttpStatus::OK) {
                return new Response(
                    HttpStatus::BAD_REQUEST,
                    ['content-type' => 'application/json'],
                    json_encode(['error' => 'Cache server is not responding correctly'])
                );
            }
        } catch (\Exception $e) {
            return new Response(
                HttpStatus::BAD_REQUEST,
                ['content-type' => 'application/json'],
                json_encode(['error' => 'Cache server is not available'])
            );
        }

        // Регистрируем сервер
        /** @var string[] $registeredList */
        $registeredList = ($this->cache->get('registered') === null ? [] : json_decode($this->cache->get('registered'), true));

        if (!isset($registeredList[$ip])) {
            $registeredList[$ip] = [
                'ip' => $ip,
                'description' => 'Auto-registered cache server',
                'registered_at' => date('Y-m-d H:i:s'),
                'status' => 'active'
            ];

            $this->cache->set(
                'registered',
                json_encode(
                    $registeredList,
                    JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            );
        }

        return new Response(
            HttpStatus::OK,
            ['content-type' => 'application/json'],
            json_encode(
                [
                    'status' => 'success',
                    'message' => 'Cache server registered successfully',
                    'server' => $registeredList[$ip]
                ]
            )
        );
    }
}
