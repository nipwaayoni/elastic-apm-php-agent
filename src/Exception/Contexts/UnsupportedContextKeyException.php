<?php

namespace Nipwaayoni\Exception\Contexts;

class UnsupportedContextKeyException extends \Exception
{
    public function __construct(array $keys, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct(sprintf('Unknown context key(s) provided: %s', implode('", "', $keys)), $code, $previous);
    }
}
