<?php

namespace Nipwaayoni\Helper;

class Timestamp implements \JsonSerializable
{
    public const MICROTIME_MULTIPLIER = 1000000;

    /**
     * @var float
     */
    private $timestamp;

    public function __construct(float $timestamp = null)
    {
        if (null === $timestamp) {
            $timestamp = microtime(true);
        }

        $this->timestamp = $timestamp;
    }

    public function asMicroSeconds(): float
    {
        return round($this->timestamp * self::MICROTIME_MULTIPLIER);
    }

    public function jsonSerialize(): float
    {
        // We use floor her to create a number that looks like an int value to APM.
        // 32-bit PHP cannot handle the integer value size represented by the float
        // and any conversion on our side produces the wrong result. Further, APM
        // requires an int value for timestamp and rejects a string or float.
        return floor($this->asMicroSeconds());
    }
}
