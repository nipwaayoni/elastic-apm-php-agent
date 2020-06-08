<?php


namespace Nipwaayoni\Tests;


class HttpTransactionContainer implements \ArrayAccess, \Countable
{
    /**
     * @var array
     */
    private $container = [];

    public function offsetExists($offset): bool
    {
        return isset($this->container[$offset]);
    }

    public function offsetGet($offset): HttpTransaction
    {
        if (!isset($this->container[$offset])) {
            throw new \Exception('Undefined transaction offset');
        }

        return new HttpTransaction($this->container[$offset]);
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->container[$offset]);
    }

    public function count(): int
    {
        return count($this->container);
    }
}