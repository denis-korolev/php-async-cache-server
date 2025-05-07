<?php

declare(strict_types=1);

namespace PhpCache\gateway;

use Amp\Http\Client\HttpClient;
use Amp\Http\Client\Request;
use Amp\Http\HttpStatus;
use Psr\Log\LoggerInterface;

class GatewayClient
{
    private HttpClient $httpClient;
    private string $gatewayUrl;
    private LoggerInterface $logger;
    private bool $isRegistered = false;

    public function __construct(
        string $gatewayPort,
        HttpClient $httpClient,
        ?LoggerInterface $logger = null
    ) {
        $this->gatewayUrl = 'http://localhost:' . $gatewayPort;
        $this->httpClient = $httpClient;
        $this->logger = $logger ?? new \Monolog\Logger('gateway_client');
    }

    public function register(string $myAddressPort): bool
    {
        if ($this->isRegistered) {
            return true;
        }

        try {
            $request = new Request(
                "{$this->gatewayUrl}/auto-register",
                'POST'
            );

            $request->setBody(
                json_encode(
                    [
                        'ip' => $myAddressPort
                    ]
                )
            );

            $response = $this->httpClient->request($request);

            if ($response->getStatus() === HttpStatus::OK) {
                $this->isRegistered = true;
                $this->logger->info(
                    'Successfully registered with gateway',
                    [
                        'gateway' => $this->gatewayUrl,
                        'server' => $myAddressPort
                    ]
                );
                return true;
            }

            $this->logger->error(
                'Failed to register with gateway ',
                [
                    'status' => $response->getStatus(),
                    'body' => $response->getBody()->buffer()
                ]
            );
            return false;
        } catch (\Exception $e) {
            $this->logger->error(
                'Error registering with gateway',
                [
                    'error' => $e->getMessage()
                ]
            );
            return false;
        }
    }

    public function unregister(string $myAddressPort): bool
    {
        if (!$this->isRegistered) {
            return true;
        }

        try {
            $request = new Request(
                "{$this->gatewayUrl}/register",
                'DELETE'
            );

            $request->setBody(
                json_encode(
                    [
                        'ip' => $myAddressPort
                    ]
                )
            );

            $response = $this->httpClient->request($request);

            if ($response->getStatus() === HttpStatus::OK) {
                $this->isRegistered = false;
                $this->logger->info(
                    'Successfully unregistered from gateway',
                    [
                        'gateway' => $this->gatewayUrl,
                        'server' => $myAddressPort
                    ]
                );
                return true;
            }

            $this->logger->error(
                'Failed to unregister from gateway',
                [
                    'status' => $response->getStatus(),
                    'body' => $response->getBody()->buffer()
                ]
            );
            return false;
        } catch (\Exception $e) {
            $this->logger->error(
                'Error unregistering from gateway',
                [
                    'error' => $e->getMessage()
                ]
            );
            return false;
        }
    }

    public function isRegistered(): bool
    {
        return $this->isRegistered;
    }
}
