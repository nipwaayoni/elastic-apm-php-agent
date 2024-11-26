<?php

namespace Nipwaayoni\Exception\Transaction;

use Nipwaayoni\Exception\ElasticApmException;

/**
 * Trying to fetch an unregistered Transaction
 */
class UnknownTransactionException extends ElasticApmException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('The transaction "%s" is not registered.', $message), $code, $previous);
    }
}
