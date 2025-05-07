<?php

declare(strict_types=1);

namespace PhpCache\server;

use Amp\Cache\LocalCache;
use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;

final class IndexAction implements RequestHandler
{
    public function __construct(private LocalCache $cache)
    {
    }
    public function handleRequest(Request $request): Response
    {
        $data = [];
        foreach ($this->cache->getIterator() as $i => $item) {
            $data[$i] = $item;
        }

        return new Response(
            HttpStatus::OK,
            ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'],
            json_encode($data, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}
