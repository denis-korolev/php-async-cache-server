<?php

declare(strict_types=1);

use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;

return [
    HttpClient::class => function () {
        return (new HttpClientBuilder())
            ->followRedirects(0)
            ->build();
    }
];
