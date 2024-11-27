<?php

namespace Nipwaayoni\Tests;

use Nipwaayoni\Agent;
use Nipwaayoni\AgentBuilder;
use Nipwaayoni\Config;
use PHPUnit\Framework\TestCase;

class AgentBuilderTest extends TestCase
{
    /**
     * This test is sort of pointless, but demonstrates how the fluent
     * building works.
     *
     * @throws \Nipwaayoni\Exception\MissingServiceNameException
     */
    public function testCanCreateAgentWithFluentCalls(): void
    {
        $agent = (new AgentBuilder())
            ->withConfig(new Config(['serviceName' => 'test']))
            ->withLabelData(['my-tag'])
            ->build();

        $this->assertInstanceOf(Agent::class, $agent);
    }

    public function testCreatesAgentWithoutConfigurationArray(): void
    {
        putenv('ELASTIC_APM_SERVICE_NAME=Test Created App');

        $agent = AgentBuilder::create();

        $this->assertEquals('Test Created App', $agent->getConfig()->serviceName());

        putenv('ELASTIC_APM_SERVICE_NAME');
    }

    public function testCreatesAgentFromConfigurationArray(): void
    {
        $agent = AgentBuilder::create(['serviceName' => 'Test Created App']);

        $this->assertEquals('Test Created App', $agent->getConfig()->serviceName());
    }

    /**
     * @param float $rate
     * @param bool $expected
     * @throws \Nipwaayoni\Exception\Helper\UnsupportedConfigurationValueException
     * @throws \Nipwaayoni\Exception\MissingServiceNameException
     *
     * @dataProvider transactionSamplingChecks
     */
    public function testAppliesTransactionSamplingStrategyToEventFactory(float $rate, bool $expected): void
    {
        $agent = (new AgentBuilder())
            ->withConfig(new Config(['serviceName' => 'test', 'transactionSampleRate' => $rate]))
            ->build();

        $transaction = $agent->startTransaction('My Transaction', []);

        $this->assertEquals($expected, $transaction->includeSamples());
    }

    public static function transactionSamplingChecks(): array
    {
        return [
            '100%' => [1.0, true],
            '0%' => [0.0, false],
        ];
    }
}
