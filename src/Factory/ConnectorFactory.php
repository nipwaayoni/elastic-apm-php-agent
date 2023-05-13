<?php


namespace Nipwaayoni\Factory;

use Nipwaayoni\Config;
use Nipwaayoni\Middleware\Connector;
use Nipwaayoni\Middleware\Credential;
use Nipwaayoni\Middleware\CredentialApiKey;
use Nipwaayoni\Middleware\CredentialNull;
use Nipwaayoni\Middleware\CredentialSecretToken;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ConnectorFactory
{
    public function makeCredential(Config $config): Credential
    {
        if ($config->apiKey() !== null) {
            return new CredentialApiKey($config->apiKey());
        }

        if ($config->secretToken() !== null) {
            return new CredentialSecretToken($config->secretToken());
        }

        return new CredentialNull();
    }

    public function makeConnector(
        string $serverUrl,
        Credential $credential,
        ClientInterface $httpClient = null,
        RequestFactoryInterface $requestFactory = null,
        StreamFactoryInterface $streamFactory = null,
        callable $preCommitCallback = null,
        callable $postCommitCallback = null
    ): Connector {
        return new Connector(
            $serverUrl,
            $credential,
            $httpClient,
            $requestFactory,
            $streamFactory,
            $preCommitCallback,
            $postCommitCallback
        );
    }
}
