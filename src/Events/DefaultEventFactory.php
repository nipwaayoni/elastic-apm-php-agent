<?php

namespace Nipwaayoni\Events;

final class DefaultEventFactory implements EventFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function newError(\Throwable $throwable, array $contexts, ?Transaction $parent = null): Error
    {
        return new Error($throwable, $contexts, $parent);
    }

    /**
     * {@inheritdoc}
     */
    public function newTransaction(string $name, array $contexts): Transaction
    {
        return new Transaction($name, $contexts);
    }

    /**
     * {@inheritdoc}
     */
    public function newSpan(string $name, EventBean $parent): Span
    {
        return new Span($name, $parent);
    }

    /**
     * {@inheritdoc}
     */
    public function newMetricset(array $set, array $tags = []): Metricset
    {
        return new Metricset($set, $tags);
    }
}
