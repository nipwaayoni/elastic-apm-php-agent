<?php

namespace Nipwaayoni\Events;

use Nipwaayoni\Exception\Events\AlreadyStartedException;
use Nipwaayoni\Helper\Encoding;
use Nipwaayoni\Helper\Timer;
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
     * @var int
     */
    private $duration = 0;

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
     * @param string $name
     * @param EventBean $parent
     */
    public function __construct(string $name, EventBean $parent)
    {
        parent::__construct([]);
        $this->name  = trim($name);
        $this->setParent($parent);
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

        $this->timer = $this->createTimer($startTime);
    }

    protected function createTimer(float $startTime = null): Timer
    {
        return new Timer($startTime);
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

    public function setDuration(int $duration)
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
                'duration'       => $this->duration,
                'name'           => Encoding::keywordField($this->getName()),
                'stacktrace'     => $this->stacktrace,
                'sync'           => false,
                'timestamp'      => $this->getTimestamp(),
            ]
        ];
    }
}
