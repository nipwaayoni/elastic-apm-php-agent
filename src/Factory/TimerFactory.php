<?php


namespace Nipwaayoni\Factory;

use Nipwaayoni\Helper\Timer;

class TimerFactory
{
    public function newTimer(float $startTime = null): Timer
    {
        return new Timer($startTime);
    }
}
