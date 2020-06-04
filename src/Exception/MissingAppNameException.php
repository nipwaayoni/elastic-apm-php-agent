<?php

namespace Nipwaayoni\Exception;

/**
 * Application Tear Up has missing App Name in Config
 */
class MissingAppNameException extends \Exception
{
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct('No app name registered in agent config.', $code, $previous);
    }
}
