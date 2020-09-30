<?php

namespace Nipwaayoni\Tests;

use Nipwaayoni\AgentBuilder;
use Nipwaayoni\ApmAgent;
use Nipwaayoni\Config;
use Nipwaayoni\Events\SampleStrategy;
use Nipwaayoni\Factory\ConnectorFactory;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function assertDurationIsWithinThreshold(int $expectedMilliseconds, float $timedDuration, float $maxOverhead = 10)
    {
        $this->assertGreaterThanOrEqual($expectedMilliseconds, $timedDuration);

        $overhead = ($timedDuration - $expectedMilliseconds);
        $this->assertLessThanOrEqual($maxOverhead, $overhead);
    }

    protected function makeAgent(array $components = [], ConnectorFactory $connectorFactory = null): ApmAgent
    {
        $builder = new AgentBuilder($connectorFactory);

        if (empty($components['config'])) {
            $components['config'] = new Config(['appName' => 'test']);
        }
        $builder->withConfig($components['config']);

        return $builder->build();
    }

    protected function makeSampleStrategy(bool $sampled): SampleStrategy
    {
        $strategy = $this->createMock(SampleStrategy::class);
        $strategy->method('sampleEvent')->willReturn($sampled);

        return $strategy;
    }

    protected function makeIncludeStrategy(): SampleStrategy
    {
        return $this->makeSampleStrategy(true);
    }

    protected function makeExcludeStrategy(): SampleStrategy
    {
        return $this->makeSampleStrategy(false);
    }
}
