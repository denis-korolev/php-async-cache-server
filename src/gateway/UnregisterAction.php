<?php

declare(strict_types=1);

namespace PhpCache\gateway;

use Amp\Cache\LocalCache;
use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;

final class UnregisterAction implements RequestHandler
{
    public function __construct(private LocalCache $cache)
    {
    }

    public function handleRequest(Request $request): Response
    {
        $body = json_decode($request->getBody()->buffer(), true);

        $ip = isset($body['ip']) ? (string)$body['ip'] : null;

        if ($ip !== null) {
            /** @var string[] $registeredList */
            $registeredList = ($this->cache->get('registered') === null ? [] : json_decode($this->cache->get('registered'), true));
            unset($registeredList[$ip]);
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
            ['content-type' => 'application/json']
        );
    }
}
