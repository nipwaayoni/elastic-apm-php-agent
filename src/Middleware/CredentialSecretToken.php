<?php

namespace Nipwaayoni\Middleware;

class CredentialSecretToken implements Credential
{
    /** @var string */
    private $secretToken;

    public function __construct(string $secretToken)
    {
        $this->secretToken = $secretToken;
    }

    public function includeAuthorizationHeader(): bool
    {
        return true;
    }

    public function authorizationHeaderValue(): string
    {
        return sprintf('Bearer: %s', $this->secretToken);
    }
}
