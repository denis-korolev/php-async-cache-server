<?php

declare(strict_types=1);

namespace PhpCache;

use Amp\Cache\LocalCache;
use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;

final class SetAction implements RequestHandler
{
    public function __construct(private LocalCache $cache)
    {
    }

    public function handleRequest(Request $request): Response
    {
        $args = $request->getAttribute(Router::class);
        $ttl = $request->getQueryParameter('ttl');

        $body = $request->getBody();
        $this->cache->set($args['key'], $body->buffer(), $ttl ? (int)$ttl : null);

        return new Response(
            HttpStatus::OK,
            ['content-type' => 'text/plain']
        );
    }
}
