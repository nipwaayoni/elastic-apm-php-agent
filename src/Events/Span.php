<?php

namespace Nipwaayoni\Events;

use Nipwaayoni\Exception\Events\AlreadyStartedException;
use Nipwaayoni\Helper\Encoding;
use Nipwaayoni\Helper\Timer;
use Nipwaayoni\Factory\TimerFactory;
use Nipwaayoni\Helper\Timestamp;
use Nipwaayoni\Traits\Events\Stacktrace;

/**
 *
 * Spans
 *
 * @link https://www.elastic.co/guide/en/apm/server/master/span-api.html
 *
 */
class Span extends TraceableEvent implements \JsonSerializable
{
    use Stacktrace;

    protected $eventType = 'span';
    /**
     * @var string
     */
    private $name;

    /**
     * @var \Nipwaayoni\Helper\Timer
     */
    private $timer;

    /**
     * @var float
     */
    private $duration = 0.0;

    /**
     * @var string
     */
    private $action = null;

    /**
     * @var string
     */
    private $type = 'request';

    /**
     * @var mixed array|null
     */
    private $stacktrace = [];

    /**
     * @var TimerFactory
     */
    private $timerFactory;

    /** @var float */
    private $startOffset;

    /** @var bool  */
    protected $isBlocking = true;

    /**
     * @param string $name
     * @param TraceableEvent $parent
     * @param TimerFactory|null $timerFactory
     */
    public function __construct(string $name, EventBean $parent, TimerFactory $timerFactory = null)
    {
        parent::__construct([], $parent);
        $this->name  = trim($name);

        // Spans are only included when the parent transaction is sampled.
        $this->includeAsSample = $parent->includeSamples();

        $this->timerFactory = $timerFactory ?? new TimerFactory();
    }

    /**
     * Start the Timer
     *
     * @param float|null $startTime
     * @return void
     * @throws \Nipwaayoni\Exception\Timer\AlreadyRunningException
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
     * Stop the Timer
     *
     * @param integer|null $duration
     *
     * @return void
     */
    public function stop(int $duration = null)
    {
        $this->timer->stop();
        $this->duration = $duration ?? round($this->timer->getDurationInMilliseconds(), 3);
    }

    public function setStartOffset(float $offset): void
    {
        $this->startOffset = $offset;
        $this->timestamp = $this->timestamp->asMicroSeconds() + $offset * 1000;
    }

    /**
    * Get the Event Name
    *
    * @return string
    */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the Span's Type
     *
     * @param string $action
     */
    public function setAction(string $action)
    {
        $this->action = trim($action);
    }

    /**
     * Set the Spans' Action
     *
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = trim($type);
    }

    public function setDuration(float $duration)
    {
        $this->duration = $duration;
    }

    /**
     * Set a complimentary Stacktrace for the Span
     *
     * @link https://www.elastic.co/guide/en/apm/server/master/span-api.html
     *
     * @param array $stacktrace
     */
    public function setStacktrace(array $stacktrace)
    {
        $this->stacktrace = $stacktrace;
    }

    /**
     * Serialize Span Event
     *
     * @link https://www.elastic.co/guide/en/apm/server/master/span-api.html
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            $this->eventType => [
                'id'             => $this->getId(),
                'transaction_id' => $this->getParentId(),
                'trace_id'       => $this->getTraceId(),
                'parent_id'      => $this->getParentId(),
                'type'           => Encoding::keywordField($this->type),
                'action'         => Encoding::keywordField($this->action),
                'context'        => $this->getContext(),
                'start'          => $this->startOffset,
                'duration'       => $this->duration,
                'name'           => Encoding::keywordField($this->getName()),
                'stacktrace'     => $this->stacktrace,
                'sync'           => $this->isBlocking,
                'timestamp'      => $this->timestamp,
            ]
        ];
    }
}
