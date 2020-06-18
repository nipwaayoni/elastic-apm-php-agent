<?php

namespace Nipwaayoni\Exception\Timer;

use Nipwaayoni\Exception\ElasticApmException;

/**
 * Trying to get the Duration of a running Timer
 */
class NotStoppedException extends ElasticApmException
{
    public function __construct(int $code = 0, \Throwable $previous = null)
    {
        parent::__construct('Can\'t get the duration of a running timer.', $code, $previous);
    }
}
