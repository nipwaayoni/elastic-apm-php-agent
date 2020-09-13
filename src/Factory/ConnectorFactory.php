<?php


namespace Nipwaayoni\Factory;


use Nipwaayoni\Middleware\Connector;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ConnectorFactory
{
    public function makeConnector(
        string $serverUrl,
        ?string $secretToken,
        ClientInterface $httpClient = null,
        RequestFactoryInterface $requestFactory = null,
        StreamFactoryInterface $streamFactory = null,
        callable $preCommitCallback = null,
        callable $postCommitCallback = null
    ): Connector
    {
        return new Connector(
            $serverUrl,
            $secretToken,
            $httpClient,
            $requestFactory,
            $streamFactory,
            $preCommitCallback,
            $postCommitCallback
        );
    }
}