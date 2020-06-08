<?php


namespace Nipwaayoni\Tests;


use Psr\Http\Message\RequestInterface;

class HttpTransaction
{
    /**
     * @var array
     */
    private $transaction;

    public function __construct(array $transaction)
    {
        $this->transaction = $transaction;
    }

    public function request(): RequestInterface
    {
        return $this->transaction['request'];
    }
}