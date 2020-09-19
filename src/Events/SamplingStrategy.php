<?php


namespace Nipwaayoni\Events;

interface SamplingStrategy
{
    public function sampleEvent(): bool;
}
