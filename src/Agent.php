<?php

namespace Nipwaayoni;

use Nipwaayoni\Events\DefaultEventFactory;
use Nipwaayoni\Events\EventFactoryInterface;
use Nipwaayoni\Stores\TransactionsStore;
use Nipwaayoni\Events\EventBean;
use Nipwaayoni\Events\Error;
use Nipwaayoni\Events\Transaction;
use Nipwaayoni\Events\Metadata;
use Nipwaayoni\Helper\Timer;
use Nipwaayoni\Helper\Config;
use Nipwaayoni\Middleware\Connector;
use Nipwaayoni\Exception\Transaction\DuplicateTransactionNameException;
use Nipwaayoni\Exception\Transaction\UnknownTransactionException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;

/**
 *
 * APM Agent
 *
 * @link https://www.elastic.co/guide/en/apm/server/master/transaction-api.html
 *
 */
class Agent
{

    /**
     * Agent Version
     *
     * @var string
     */
    const VERSION = '7.0.0';

    /**
     * Agent Name
     *
     * @var string
     */
    const NAME = 'elasticapm-php';

    /**
     * Config Store
     *
     * @var \Nipwaayoni\Helper\Config
     */
    private $config;

    /**
     * Transactions Store
     *
     * @var \Nipwaayoni\Stores\TransactionsStore
     */
    private $transactionsStore;

    /**
     * Apm Timer
     *
     * @var \Nipwaayoni\Helper\Timer
     */
    private $timer;

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
     * @param array                 $config
     * @param array                 $sharedContext Set shared contexts such as user and tags
     * @param EventFactoryInterface $eventFactory  Alternative factory to use when creating event objects
     *
     * @return void
     */
    public function __construct(
        array $config,
        array $sharedContext = [],
        EventFactoryInterface $eventFactory = null,
        TransactionsStore $transactionsStore = null,
        ClientInterface $client = null,
        RequestFactoryInterface $requestFactory = null,
        StreamFactoryInterface $streamFactory = null
    ) {
        // Init Agent Config
        $this->config = new Config($config);

        $client = $client ?: HttpClientDiscovery::find();
        $requestFactory = $requestFactory ?: Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory = $streamFactory ?: Psr17FactoryDiscovery::findStreamFactory();

        // Use the custom event factory or create a default one
        $this->eventFactory = $eventFactory ?? new DefaultEventFactory();

        // Init the Shared Context
        $this->sharedContext['user']   = $sharedContext['user'] ?? [];
        $this->sharedContext['custom'] = $sharedContext['custom'] ?? [];
        $this->sharedContext['tags']   = $sharedContext['tags'] ?? [];

        // Let's misuse the context to pass the environment variable and cookies
        // config to the EventBeans and the getContext method
        // @see https://github.com/philkra/elastic-apm-php-agent/issues/27
        // @see https://github.com/philkra/elastic-apm-php-agent/issues/30
        $this->sharedContext['env'] = $this->config->get('env', []);
        $this->sharedContext['cookies'] = $this->config->get('cookies', []);

        // Initialize Event Stores
        $this->transactionsStore = $transactionsStore ?? new TransactionsStore();

        // Init the Transport "Layer"
        $this->connector = new Connector($client, $requestFactory, $streamFactory, $this->config);
        $this->connector->putEvent(new Metadata([], $this->config));

        // Start Global Agent Timer
        $this->timer = new Timer();
        $this->timer->start();
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
     * @throws \Nipwaayoni\Exception\Transaction\DuplicateTransactionNameException
     *
     * @param string $name
     * @param array  $context
     *
     * @return Transaction
     */
    public function startTransaction(string $name, array $context = [], float $start = null): Transaction
    {
        // Create and Store Transaction
        $this->transactionsStore->register(
            $this->factory()->newTransaction($name, array_replace_recursive($this->sharedContext, $context), $start)
        );

        // Start the Transaction
        $transaction = $this->transactionsStore->fetch($name);

        if (null === $start) {
            $transaction->start();
        }

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
        $this->putEvent($this->factory()->newError($thrown, array_replace_recursive($this->sharedContext, $context), $parent));
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
     * @return \Nipwaayoni\Helper\Config
     */
    public function getConfig(): \Nipwaayoni\Helper\Config
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

    /**
     * Flush the Queue Payload
     *
     * @link https://www.php.net/manual/en/language.oop5.decon.php#object.destruct
     */
    public function __destruct()
    {
        $this->send();
    }
}
