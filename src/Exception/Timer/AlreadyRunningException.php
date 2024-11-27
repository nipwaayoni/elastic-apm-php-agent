<?php

namespace Nipwaayoni\Exception\Timer;

use Nipwaayoni\Exception\ElasticApmException;

/**
 * Trying to stop a Timer that is already running
 */
class AlreadyRunningException extends ElasticApmException
{
    public function __construct(int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct('Can\'t start a timer which is already running.', $code, $previous);
    }
}
