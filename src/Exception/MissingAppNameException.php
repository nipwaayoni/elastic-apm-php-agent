<?php

namespace Nipwaayoni\Exception;

/**
 * Application Tear Up has missing App Name in Config
 */
class MissingAppNameException extends ElasticApmException
{
    public function __construct(int $code = 0, \Throwable $previous = null)
    {
        parent::__construct('No app name registered in agent config.', $code, $previous);
    }
}
