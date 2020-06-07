<?php

namespace Nipwaayoni\Exception\Helper;

use Nipwaayoni\Exception\ElasticApmException;

class UnsupportedConfigurationValueException extends ElasticApmException
{
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct(sprintf('The provided configuration option "%s" is not supported.', $message), $code, $previous);
    }
}
