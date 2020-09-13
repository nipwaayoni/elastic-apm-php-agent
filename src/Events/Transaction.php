<?php

namespace Nipwaayoni\Events;

use Nipwaayoni\Exception\Events\AlreadyStartedException;
use Nipwaayoni\Factory\TimerFactory;
use Nipwaayoni\Helper\Timer;
use Nipwaayoni\Helper\Encoding;
use Nipwaayoni\Helper\Timestamp;

/**
 *
 * Transaction Event
 *
 * @link https://www.elastic.co/guide/en/apm/server/master/transaction-api.html
 *
 */
class Transaction extends TraceableEvent implements \JsonSerializable
{
    protected $eventType = 'transaction';

    /**
     * Transaction Name
     *
     * @var string
     */
    private $name;

    /**
     * @var TimerFactory
     */
    private $timerFactory;

    /**
     * @var \Nipwaayoni\Helper\Timer
     */
    private $timer;

    /**
     * Summary of this Transaction
     *
     * @var array
     */
    private $summary = [
        'duration'  => 0.0,
        'backtrace' => null,
        'headers'   => []
    ];

    /**
     * @var int
     */
    private $backtraceLimit = 0;

    /**
     * Create the Transaction
     *
     * @param string $name
     * @param array $contexts
     * @param TimerFactory|null $timerFactory
     */
    public function __construct(string $name, array $contexts, TimerFactory $timerFactory = null)
    {
        parent::__construct($contexts);
        $this->setTransactionName($name);
        $this->timerFactory = $timerFactory ?? new TimerFactory();
    }

    /**
     * Start the Transaction
     *
     * @param float|null $startTime
     * @return void
     * @throws AlreadyStartedException
     */
    public function start(float $startTime = null)
    {
        if (null !== $this->timer) {
            throw new AlreadyStartedException();
        }

        $this->timer = $this->timerFactory->newTimer($startTime);
        if ($this->timer->isNotStarted()) {
            $this->timer->start();
        }
        $this->timestamp = new Timestamp($this->timer->getStartTime());
    }

    /**
     * Stop the Transaction
     *
     * @param integer|null $duration
     *
     * @return void
     */
    public function stop(int $duration = null)
    {
        // Stop the Timer
        $this->timer->stop();

        // Store Summary
        $this->summary['duration']  = $duration ?? round($this->timer->getDurationInMilliseconds(), 3);
        $this->summary['headers']   = (function_exists('xdebug_get_headers') === true) ? xdebug_get_headers() : [];
        $this->summary['backtrace'] = debug_backtrace($this->backtraceLimit);
    }

    /**
    * Set the Transaction Name
    *
    * @param string $name
    *
    * @return void
    */
    public function setTransactionName(string $name)
    {
        $this->name = $name;
    }

    /**
    * Get the Transaction Name
    *
    * @return string
    */
    public function getTransactionName(): string
    {
        return $this->name;
    }

    /**
    * Get the Summary of this Transaction
    *
    * @return array
    */
    public function getSummary(): array
    {
        return $this->summary;
    }

    /**
     * Set the Max Depth/Limit of the debug_backtrace method
     *
     * @link http://php.net/manual/en/function.debug-backtrace.php
     * @link https://github.com/philkra/elastic-apm-php-agent/issues/55
     *
     * @param int $limit
     */
    public function setBacktraceLimit(int $limit)
    {
        $this->backtraceLimit = $limit;
    }

    public function includeSamples(): bool
    {
        return $this->sampleStrategy->sampleEvent();
    }

    /**
     * Serialize Transaction Event
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $context = null;

        if ($this->includeSamples()) {
            $context = $this->getContext();
        }

        return [
            $this->eventType => [
                'trace_id'   => $this->getTraceId(),
                'id'         => $this->getId(),
                'parent_id'  => $this->getParentId(),
                'type'       => Encoding::keywordField($this->getMetaType()),
                'duration'   => $this->summary['duration'],
                'timestamp'  => $this->getTimestamp(),
                'result'     => $this->getMetaResult(),
                'name'       => Encoding::keywordField($this->getTransactionName()),
                'context'    => $context,
                'sampled'    => $this->includeSamples(),
                'span_count' => [
                    'started' => 0,
                    'dropped' => 0,
                ],
            ]
        ];
    }
}
