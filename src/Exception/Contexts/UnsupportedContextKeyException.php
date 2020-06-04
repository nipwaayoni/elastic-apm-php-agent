<?php

namespace Nipwaayoni\Exception\Contexts;

class UnsupportedContextKeyException extends \Exception
{
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct(sprintf('Unknown context key provided: %s', $message), $code, $previous);
    }
}
