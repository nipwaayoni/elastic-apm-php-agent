<?php

namespace Nipwaayoni\Middleware;

class CredentialApiKey implements Credential
{
    /** @var string */
    private $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function includeAuthorizationHeader(): bool
    {
        return true;
    }

    public function authorizationHeaderValue(): string
    {
        return sprintf('ApiKey: %s', $this->apiKey);
    }
}
