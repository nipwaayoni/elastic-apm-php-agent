<?php

namespace Nipwaayoni\Middleware;

use Nipwaayoni\Exception\ElasticApmException;

class CredentialNull implements Credential
{
    public function includeAuthorizationHeader(): bool
    {
        return false;
    }

    public function authorizationHeaderValue(): string
    {
        throw new ElasticApmException('Credential does not support Authorization header usage');
    }

}
