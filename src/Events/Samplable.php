<?php


namespace Nipwaayoni\Events;

interface Samplable
{
    /**
     * Sets the strategy used for determining of the object should be sampled.
     *
     * @param SampleStrategy $strategy
     */
    public function sampleStrategy(SampleStrategy $strategy): void;

    /**
     * Indicates if the object's descendent events should be included as samples.
     *
     * @return bool
     */
    public function includeSamples(): bool;

    /**
     * Indicates if this object itself should be included as a sample.
     *
     * @return bool
     */
    public function isSampled(): bool;
}
