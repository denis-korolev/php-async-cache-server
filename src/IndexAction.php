<?php

declare(strict_types=1);

namespace PhpCache;

use Amp\Cache\LocalCache;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\HttpStatus;

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
            ['content-type' => 'application/json'],
            json_encode($data, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}
