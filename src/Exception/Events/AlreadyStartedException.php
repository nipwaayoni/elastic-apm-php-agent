<?php

namespace Nipwaayoni\Exception\Events;

use Nipwaayoni\Exception\ElasticApmException;

class AlreadyStartedException extends ElasticApmException
{
    public function __construct(int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct('The target event has already been started.', $code, $previous);
    }
}
