<?php

namespace PhpCache\gateway\services;

use Amp\Http\Client\HttpClient;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;

class ProxyRequestService
{
    public function __construct(
        private HttpClient $httpClient
    ) {
    }
    public function makeRequest(Request $request): Response
    {
        return $this->httpClient->request($request);
    }
}
