<?php

declare(strict_types=1);

use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use DI\Container;
use PhpCache\gateway\GatewayClient;

return [
    HttpClient::class => function () {
        return (new HttpClientBuilder())
            ->followRedirects(0)
            ->build();
    },
    GatewayClient::class => function (Container $container) {
        return new GatewayClient(
            (string)getenv('GATEWAY_PORT'),
            $container->get(HttpClient::class)
        );
    }
];
