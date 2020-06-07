<?php

namespace Nipwaayoni\Exception\Transaction;

use Nipwaayoni\Exception\ElasticApmException;

/**
 * Trying to register a already registered Transaction
 */
class DuplicateTransactionNameException extends ElasticApmException
{
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct(sprintf('A transaction with the name %s is already registered.', $message), $code, $previous);
    }
}
