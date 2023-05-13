<?php

namespace Nipwaayoni\Middleware;

interface Credential
{
    public function includeAuthorizationHeader(): bool;

    public function authorizationHeaderValue(): string;
}
