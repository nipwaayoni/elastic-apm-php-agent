<?php

namespace Nipwaayoni\Stores;

use Nipwaayoni\Events\Transaction;
use Nipwaayoni\Exception\Transaction\DuplicateTransactionNameException;

/**
 *
 * Store for the Transaction Events
 *
 */
class TransactionsStore extends Store
{
    /**
     * Register a Transaction
     *
     * @throws \Nipwaayoni\Exception\Transaction\DuplicateTransactionNameException
     *
     * @param \Nipwaayoni\Events\Transaction $transaction
     *
     * @return void
     */
    public function register(Transaction $transaction)
    {
        $name = $transaction->getTransactionName();

        // Do not override the
        if (isset($this->store[$name]) === true) {
            throw new DuplicateTransactionNameException($name);
        }

        // Push to Store
        $this->store[$name] = $transaction;
    }

    /**
     * Fetch a Transaction from the Store
     *
     * @param final string $name
     *
     * @return mixed: \Nipwaayoni\Events\Transaction | null
     */
    public function fetch(string $name)
    {
        return $this->store[$name] ?? null;
    }

    /**
     * Serialize the Transactions Events Store
     *
     * @return array
     */
    public function jsonSerialize() : array
    {
        return array_values($this->store);
    }
}
