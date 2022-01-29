<?php

namespace Nipwaayoni\Events;

class TransactionSampleStrategy implements SampleStrategy
{
    /**
     * @var float
     */
    private $rate;

    public function __construct(float $rate = 1.0)
    {
        $this->rate = $rate;
    }

    public function sampleEvent(): bool
    {
        $rand = mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax();

        return $rand <= $this->rate;
    }
}
