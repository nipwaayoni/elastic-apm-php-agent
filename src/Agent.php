<?php

namespace Nipwaayoni;

use Nipwaayoni\Events\EventFactoryInterface;
use Nipwaayoni\Contexts\ContextCollection;
use Nipwaayoni\Stores\TransactionsStore;
use Nipwaayoni\Events\EventBean;
use Nipwaayoni\Events\Transaction;
use Nipwaayoni\Events\Metadata;
use Nipwaayoni\Middleware\Connector;
use Nipwaayoni\Exception\Transaction\DuplicateTransactionNameException;
use Nipwaayoni\Exception\Transaction\UnknownTransactionException;
use Psr\Http\Message\ResponseInterface;

/**
 *
 * APM Agent
 *
 * @link https://www.elastic.co/guide/en/apm/server/master/transaction-api.html
 *
 */
class Agent implements ApmAgent
{

    /**
     * Agent Version
     *
     * @var string
     */
    const VERSION = '7.1.0';

    /**
     * Agent Name
     *
     * @var string
     */
    const NAME = 'elasticapm-php';

    /**
     * Config Store
     *
     * @var \Nipwaayoni\Config
     */
    private $config;

    /**
     * Transactions Store
     *
     * @var \Nipwaayoni\Stores\TransactionsStore
     */
    private $transactionsStore;

    /**
     * Common/Shared Contexts for Errors and Transactions
     *
     * @var array
     */
    private $sharedContext = [
      'user'   => [],
      'custom' => [],
      'tags'   => []
    ];

    /**
     * @var EventFactoryInterface
     */
    private $eventFactory;

    /**
     * @var Connector
     */
    private $connector;

    /**
     * Setup the APM Agent
     *
     * @param Config $config
     * @param ContextCollection $sharedContext Set shared contexts such as user and tags
     * @param Connector $connector
     * @param EventFactoryInterface $eventFactory Alternative factory to use when creating event objects
     * @param TransactionsStore $transactionsStore
     */
    public function __construct(
        Config $config,
        ContextCollection $sharedContext,
        Connector $connector,
        EventFactoryInterface $eventFactory,
        TransactionsStore $transactionsStore
    ) {
        $this->config = $config;

        $this->sharedContext = $sharedContext;

        $this->eventFactory = $eventFactory;

        $this->transactionsStore = $transactionsStore;

        $this->connector = $connector;
        $this->connector->putEvent(new Metadata([], $this->config));
    }

    /**
     * Event Factory
     *
     * @return EventFactoryInterface
     */
    public function factory(): EventFactoryInterface
    {
        return $this->eventFactory;
    }

    /**
     * Query the Info endpoint of the APM Server
     *
     * @link https://www.elastic.co/guide/en/apm/server/7.3/server-info.html
     *
     * @return Response
     */
    public function info(): ResponseInterface
    {
        return $this->connector->getInfo();
    }

    /**
     * Start the Transaction capturing
     *
     * @param string $name
     * @param array $context
     * @param float|null $start
     * @return Transaction
     * @throws DuplicateTransactionNameException
     */
    public function startTransaction(string $name, array $context = [], float $start = null): Transaction
    {
        $eventContext = $this->sharedContext->merge(new ContextCollection($context));

        // Create and Store Transaction
        $this->transactionsStore->register(
            $this->factory()->newTransaction($name, $eventContext->toArray())
        );

        // Start the Transaction
        $transaction = $this->transactionsStore->fetch($name);

        $transaction->start($start);

        return $transaction;
    }

    /**
     * Stop the Transaction
     *
     * @throws \Nipwaayoni\Exception\Transaction\UnknownTransactionException
     *
     * @param string $name
     * @param array $meta, Def: []
     *
     * @return void
     */
    public function stopTransaction(string $name, array $meta = [])
    {
        $this->getTransaction($name)->setBacktraceLimit($this->config->get('backtraceLimit', 0));
        $this->getTransaction($name)->stop();
        $this->getTransaction($name)->setMeta($meta);
    }

    /**
     * Get a Transaction
     *
     * @throws \Nipwaayoni\Exception\Transaction\UnknownTransactionException
     *
     * @param string $name
     *
     * @return Transaction
     */
    public function getTransaction(string $name)
    {
        $transaction = $this->transactionsStore->fetch($name);
        if ($transaction === null) {
            throw new UnknownTransactionException($name);
        }

        return $transaction;
    }

    /**
     * Register a Thrown Exception, Error, etc.
     *
     * @link http://php.net/manual/en/class.throwable.php
     *
     * @param \Throwable  $thrown
     * @param array       $context, Def: []
     * @param Transaction $parent, Def: null
     *
     * @return void
     */
    public function captureThrowable(\Throwable $thrown, array $context = [], ?Transaction $parent = null)
    {
        $eventContext = $this->sharedContext->merge(new ContextCollection($context));

        $this->putEvent($this->factory()->newError($thrown, $eventContext->toArray(), $parent));
    }

    /**
     * Put an Event to the Events Pool
     */
    public function putEvent(EventBean $event)
    {
        $this->connector->putEvent($event);
    }

    /**
     * Get the Agent Config
     *
     * @return \Nipwaayoni\Config
     */
    public function getConfig(): \Nipwaayoni\Config
    {
        return $this->config;
    }

    /**
     * Send Data to APM Service
     *
     * @link https://github.com/philkra/elastic-apm-laravel/issues/22
     * @link https://github.com/philkra/elastic-apm-laravel/issues/26
     *
     * @return bool
     */
    public function send(): bool
    {
        // Is the Agent enabled ?
        if ($this->config->get('active') === false) {
            $this->transactionsStore->reset();
            return true;
        }

        // Put the preceding Metadata
        // TODO -- add context ?
        if ($this->connector->isPayloadSet() === false) {
            $this->putEvent(new Metadata([], $this->config));
        }

        // Start Payload commitment
        foreach ($this->transactionsStore->list() as $event) {
            $this->connector->putEvent($event);
        }
        $this->transactionsStore->reset();
        return $this->connector->commit();
    }
}
