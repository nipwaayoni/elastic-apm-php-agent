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

    public function asMicroSeconds(): int
    {
        return (int) sprintf("%.0f", floor($this->timestamp * self::MICROTIME_MULTIPLIER));
    }

    public function jsonSerialize(): int
    {
        return $this->asMicroSeconds();
    }
}
