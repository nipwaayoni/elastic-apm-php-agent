<?php


namespace Nipwaayoni\Events;

class DefaultSamplingStrategy implements SamplingStrategy
{
    public function sampleEvent(): bool
    {
        return true;
    }
}
