<?php

namespace Nipwaayoni\Exception;

use Nipwaayoni\ApmAgent;
use Throwable;

class UnsupportedApmAgentImplementationException extends ElasticApmException
{
    public function __construct($class, $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf('Agent must implement %s. Provided class %s does not.', ApmAgent::class, $class), $code, $previous);
    }
}
