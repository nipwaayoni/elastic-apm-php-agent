<?php


namespace Nipwaayoni\Events;

class DefaultSampleStrategy implements SampleStrategy
{
    public function sampleEvent(): bool
    {
        return true;
    }
}
