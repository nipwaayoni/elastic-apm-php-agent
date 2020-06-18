<?php

namespace Nipwaayoni\Tests;

use Nipwaayoni\Agent;
use Nipwaayoni\AgentBuilder;
use Nipwaayoni\Config;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function assertDurationIsWithinThreshold(int $expectedMilliseconds, float $timedDuration, float $maxOverhead = 10)
    {
        $this->assertGreaterThanOrEqual($expectedMilliseconds, $timedDuration);

        $overhead = ($timedDuration - $expectedMilliseconds);
        $this->assertLessThanOrEqual($maxOverhead, $overhead);
    }

    protected function makeAgent(array $components = []): Agent
    {
        $builder = new AgentBuilder();

        if (empty($components['config'])) {
            $components['config'] = new Config(['appName' => 'test']);
        }
        $builder->withConfig($components['config']);

        return $builder->build();
    }
}
