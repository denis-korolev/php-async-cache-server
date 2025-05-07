<?php

namespace PhpCache\gateway\services;

use Amp\Http\Client\HttpClient;
use Amp\Http\Client\Request as ClientRequest;

class ExternalHttpRequest
{
    public function __construct(
        private HttpClient $httpClient
    ) {
    }
    public function checkClient(string $url): int
    {
        $clientRequest = new ClientRequest($url);
        $response = $this->httpClient->request($clientRequest);
        return $response->getStatus();
    }
}
