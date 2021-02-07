<?php

namespace Nipwaayoni\Events;

use Nipwaayoni\Exception\InvalidTraceContextHeaderException;
use Nipwaayoni\Helper\DistributedTracing;
use Psr\Http\Message\RequestInterface;

/**
 *
 * Traceable Event -- Distributed Tracing
 *
 */
class TraceableEvent extends EventBean // TODO refactor to DistributedTrace or something
{
    /**
     * @var EventBean
     */
    private $parent;

    /**
     * Create the Transaction
     *
     * @param array $contexts
     * @param EventBean $parent
     * @throws \Exception
     */
    public function __construct(array $contexts, ?EventBean $parent = null)
    {
        parent::__construct($contexts, $parent);
        $this->parent = $parent;
        $this->setTraceContext();
    }

    /**
     * Get the Distributed Tracing Value of this Event
     *
     * @return string
     */
    public function getDistributedTracing(): string
    {
        $id = null !== $this->parent ? $this->getParentId() : $this->getId();

        return (string) new DistributedTracing($this->getTraceId(), $id);
    }

    /**
     * Set Trace context
     *
     * @throws \Exception
     */
    private function setTraceContext()
    {
        if (null !== $this->parent) {
            return;
        }

        // TODO test behavior with and without parent
        // TODO set trace-flags
        // TODO set trace-flags sampled based on parent
        // TODO provide list of candidate trace headers and use first
        // TODO provide method to add trace header to RequestInterface object

        // Is one of the Traceparent Headers populated ?
        $header = $_SERVER['HTTP_ELASTIC_APM_TRACEPARENT'] ?? ($_SERVER['HTTP_TRACEPARENT'] ?? null);
        if ($header !== null && DistributedTracing::isValidHeader($header) === true) {
            try {
                $traceParent = DistributedTracing::createFromHeader($header);

                $this->setTraceId($traceParent->getTraceId());
                $this->setParentId($traceParent->getParentId());
            } catch (InvalidTraceContextHeaderException $e) {
                $this->setTraceId(self::generateRandomBitsInHex(self::TRACE_ID_BITS));
            }
        } else {
            $this->setTraceId(self::generateRandomBitsInHex(self::TRACE_ID_BITS));
        }
    }

    public function traceHeaderAsArray(): array
    {
        return [
            'name' => DistributedTracing::HEADER_NAME,
            'value' => $this->getDistributedTracing()
        ];
    }

    public function traceHeaderAsString(): string
    {
        return sprintf('%s: %s', DistributedTracing::HEADER_NAME, $this->getDistributedTracing());
    }

    public function addTraceHeaderToRequest(RequestInterface $request): RequestInterface
    {
        return $request->withHeader(DistributedTracing::HEADER_NAME, $this->getDistributedTracing());
    }
    
    public function getParent(): ?EventBean
    {
        return $this->parent;
    }
}
