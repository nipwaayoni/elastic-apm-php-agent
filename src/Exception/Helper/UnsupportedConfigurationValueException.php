<?php

namespace Nipwaayoni\Exception\Helper;

class UnsupportedConfigurationValueException extends \Exception
{
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct(sprintf('The provided configuration option "%s" is not supported.', $message), $code, $previous);
    }
}
