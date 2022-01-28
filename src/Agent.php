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
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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
    public const VERSION = '7.3.0';

    /**
     * Agent Name
     *
     * @var string
     */
    public const NAME = 'elasticapm-php';

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

    /** @var LoggerInterface */
    private $logger;

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
        $this->connector->useHttpUserAgentString($this->httpUserAgent());
        // TODO Why is the metadata added here and conditionally in the send() method?
        $this->connector->putEvent(new Metadata([], $this->config, $this->agentMetadata()));

        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function agentMetadata(): array
    {
        return [
            'name' => self::NAME,
            'version' => self::VERSION,
        ];
    }

    public function httpUserAgent(): string
    {
        return sprintf('%s/%s', self::NAME, self::VERSION);
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
     * @return ResponseInterface
     */
    public function info(): ResponseInterface
    {
        return $this->connector->getInfo();
    }

    // TODO drop any mention of support for pre-7.0 and v1
    // TODO all event creation originates in Agent, and everything is tracked automatically (remove addEvent)
    // TODO Agent must be aware of current Transaction and Transaction must be aware of current Span
    // TODO implement Agent::startTransactionFromHttpRequest(RequestInterface $request): Transaction
    // TODO implement Agent::createTransaction(TransactionData $data): Transaction
    // TODO implement Agent::startTransaction(string $name, ...): TimedTransaction
    // TODO implement Agent::captureError(ErrorData $data): Error (to Span, Transaction or global)
    // TODO implement TimedTransaction::stop(): Transaction
    // TODO implement Transaction::createSpan(SpanData $data): Span
    // TODO implement Transaction::startSpan(...): TimedSpan
    // TODO implement TimedSpan::stop(): Span
    // TODO all Events should have explicit toJson() method (they may use json_encode internally)

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

        $this->logger->debug('Started transaction: ' . $name);

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
        $this->getTransaction($name)->setBacktraceLimit($this->config->stackTraceLimit());
        $this->getTransaction($name)->stop();
        $this->getTransaction($name)->setMeta($meta);

        $this->logger->debug('Stopped transaction: ' . $name);
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

        $this->logger->debug('Captured throwable: ' . $thrown->getMessage());
    }

    /**
     * Put an Event to the Events Pool
     */
    public function putEvent(EventBean $event)
    {
        if (!$event->isSampled()) {
            $this->logger->debug('Skipped adding event (not sampled): ' . $event->getEventType());
            return;
        }

        // TODO reconcile putting directly vs accumulating in TransactionStore
        $this->connector->putEvent($event);

        $this->logger->debug('Added event: ' . $event->getEventType());
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
     * @return void
     */
    public function send(): void
    {
        // Is the Agent enabled ?
        if ($this->config->notEnabled()) {
            $this->transactionsStore->reset();
            $this->logger->debug('Agent is disabled, did not send data');
            return;
        }

        // Put the preceding Metadata
        // TODO -- add context ?
        if ($this->connector->isPayloadSet() === false) {
            $this->putEvent(new Metadata([], $this->config, $this->agentMetadata()));
            $this->logger->debug('Payload is empty, added metadata');
        }

        // Start Payload commitment
        foreach ($this->transactionsStore->list() as $event) {
            $this->connector->putEvent($event);
        }

        $this->logger->debug('Added transactions to connector');

        $this->transactionsStore->reset();

        $this->connector->commit();

        $this->logger->debug('Sent data to Elastic APM host');
    }
}
