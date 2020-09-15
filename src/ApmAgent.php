<?php


namespace Nipwaayoni;

use Nipwaayoni\Events\EventBean;
use Nipwaayoni\Events\EventFactoryInterface;
use Nipwaayoni\Events\Transaction;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;

interface ApmAgent extends LoggerAwareInterface
{
    public function agentMetadata(): array;
    public function httpUserAgent(): string;
    public function factory(): EventFactoryInterface;
    public function info(): ResponseInterface;
    public function startTransaction(string $name, array $context = [], float $start = null): Transaction;
    public function stopTransaction(string $name, array $meta = []);
    public function getTransaction(string $name);
    public function captureThrowable(\Throwable $thrown, array $context = [], ?Transaction $parent = null);
    public function putEvent(EventBean $event);
    public function getConfig(): Config;
    public function send(): void;
}
