<?php

namespace Nipwaayoni\Tests;

use Nipwaayoni\Agent;
use Nipwaayoni\Config;
use Nipwaayoni\Events\EventBean;
use Nipwaayoni\Events\Metadata;
use Nipwaayoni\Factory\ConnectorFactory;
use Nipwaayoni\Middleware\Connector;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test Case for @see \Nipwaayoni\Agent
 */
final class AgentTest extends TestCase
{
    /**
     * @covers \Nipwaayoni\Agent::agentMetadata
     */
    public function testReturnsMetaData(): void
    {
        $agent = $this->makeAgent(['config' => new Config([ 'serviceName' => 'phpunit_1' ])]);

        $metadata = $agent->agentMetadata();

        $this->assertEquals(['name' => Agent::NAME, 'version' => Agent::VERSION], $metadata);
    }

    /**
     * @covers \Nipwaayoni\Agent::agentMetadata
     */
    public function testReturnsHttpUserAgent(): void
    {
        $agent = $this->makeAgent(['config' => new Config([ 'serviceName' => 'phpunit_1' ])]);

        $this->assertEquals(Agent::NAME . '/' . Agent::VERSION, $agent->httpUserAgent());
    }

    /**
     * @covers \Nipwaayoni\Agent::__construct
     * @covers \Nipwaayoni\Agent::startTransaction
     * @covers \Nipwaayoni\Agent::stopTransaction
     * @covers \Nipwaayoni\Agent::getTransaction
     */
    public function testStartAndStopATransaction()
    {
        $agent = $this->makeAgent(['config' => new Config([ 'serviceName' => 'phpunit_1', 'active' => false, ])]);

        // Create a Transaction, wait and Stop it
        $name = 'trx';
        $agent->startTransaction($name);
        usleep(10 * 1000); // sleep milliseconds
        $agent->stopTransaction($name);

        // Transaction Summary must be populated
        $summary = $agent->getTransaction($name)->getSummary();

        $this->assertArrayHasKey('duration', $summary);
        $this->assertArrayHasKey('backtrace', $summary);

        // Expect duration in milliseconds
        $this->assertDurationIsWithinThreshold(10, $summary['duration']);
        $this->assertNotEmpty($summary['backtrace']);
    }

    /**
     * @covers \Nipwaayoni\Agent::__construct
     * @covers \Nipwaayoni\Agent::startTransaction
     * @covers \Nipwaayoni\Agent::stopTransaction
     * @covers \Nipwaayoni\Agent::getTransaction
     */
    public function testStartAndStopATransactionWithExplicitStart()
    {
        $agent = $this->makeAgent(['config' => new Config([ 'serviceName' => 'phpunit_1', 'active' => false, ])]);

        // Create a Transaction, wait and Stop it
        $name = 'trx';
        $agent->startTransaction($name, [], microtime(true) - 1);
        usleep(500 * 1000); // sleep milliseconds
        $agent->stopTransaction($name);

        // Transaction Summary must be populated
        $summary = $agent->getTransaction($name)->getSummary();

        $this->assertArrayHasKey('duration', $summary);
        $this->assertArrayHasKey('backtrace', $summary);

        // Expect duration in milliseconds
        $this->assertDurationIsWithinThreshold(1500, $summary['duration'], 150);
        $this->assertNotEmpty($summary['backtrace']);
    }

    /**
     * @depends testStartAndStopATransaction
     *
     * @covers \Nipwaayoni\Agent::__construct
     * @covers \Nipwaayoni\Agent::getTransaction
     */
    public function testForceErrorOnUnknownTransaction()
    {
        $agent = $this->makeAgent(['config' => new Config([ 'serviceName' => 'phpunit_x', 'active' => false, ])]);

        $this->expectException(\Nipwaayoni\Exception\Transaction\UnknownTransactionException::class);

        // Let it go boom!
        $agent->getTransaction('unknown');
    }

    /**
     * @depends testForceErrorOnUnknownTransaction
     *
     * @covers \Nipwaayoni\Agent::__construct
     * @covers \Nipwaayoni\Agent::stopTransaction
     */
    public function testForceErrorOnUnstartedTransaction()
    {
        $agent = $this->makeAgent(['config' => new Config([ 'serviceName' => 'phpunit_2', 'active' => false, ])]);

        $this->expectException(\Nipwaayoni\Exception\Transaction\UnknownTransactionException::class);

        // Stop an unstarted Transaction and let it go boom!
        $agent->stopTransaction('unknown');
    }

    public function testAddsSampledEvents(): void
    {
        /** @var Connector|MockObject $connector */
        $connector = $this->createMock(Connector::class);
        $connector->expects($this->exactly(2))->method('putEvent');

        /** @var ConnectorFactory|MockObject $connectorFactory */
        $connectorFactory = $this->createMock(ConnectorFactory::class);
        $connectorFactory->expects($this->once())->method('makeConnector')
            ->willreturn($connector);

        /** @var EventBean|MockObject $event */
        $event = $this->createMock(EventBean::class);
        $event->expects($this->once())->method('isSampled')->willReturn(true);

        $agent = $this->makeAgent(['config' => new Config([ 'serviceName' => 'phpunit_1' ])], $connectorFactory);

        $agent->putEvent($event);
    }

    public function testDoesNotAddNonSampledEvents(): void
    {
        /** @var Connector|MockObject $connector */
        $connector = $this->createMock(Connector::class);
        $connector->expects($this->once())->method('putEvent');

        /** @var ConnectorFactory|MockObject $connectorFactory */
        $connectorFactory = $this->createMock(ConnectorFactory::class);
        $connectorFactory->expects($this->once())->method('makeConnector')
            ->willreturn($connector);

        /** @var EventBean|MockObject $event */
        $event = $this->createMock(EventBean::class);
        $event->expects($this->once())->method('isSampled')->willReturn(false);

        $agent = $this->makeAgent(['config' => new Config([ 'serviceName' => 'phpunit_1' ])], $connectorFactory);

        $agent->putEvent($event);
    }

    public function testReturnsSharedContext(): void
    {
        $agent = $this->makeAgent([
            'config' => new Config([ 'serviceName' => 'phpunit_1' ]),
            'user' => ['username' => 'doej'],
            'custom' => ['module' => 'Introduction'],
        ]);

        $sharedContext = $agent->getSharedContext();

        $this->assertEquals(['username' => 'doej'], $sharedContext->user());
        $this->assertEquals(['module' => 'Introduction'], $sharedContext->custom());
    }

    public function testAddsSharedContextOnConnectorSetup(): void
    {
        /** @var Connector|MockObject $connector */
        $connector = $this->createMock(Connector::class);
        $connector->expects($this->once())->method('putEvent')
            ->with($this->callback(function (Metadata $metadata) {
                $this->assertEquals(['username' => 'doej'], $metadata->getSubContext('user'));
                $this->assertEquals(['module' => 'Introduction'], $metadata->getSubContext('custom'));
                return true;
            }));

        /** @var ConnectorFactory|MockObject $connectorFactory */
        $connectorFactory = $this->createMock(ConnectorFactory::class);
        $connectorFactory->expects($this->once())->method('makeConnector')
            ->willreturn($connector);

        $agent = $this->makeAgent([
            'config' => new Config([ 'serviceName' => 'phpunit_1' ]),
            'user' => ['username' => 'doej'],
            'custom' => ['module' => 'Introduction'],
        ], $connectorFactory);

    }

    public function testAddsSharedContextToConnectorIfNotSet(): void
    {
        /** @var Connector|MockObject $connector */
        $connector = $this->createMock(Connector::class);
        $connector->expects($this->exactly(2))->method('putEvent')
            ->with($this->callback(function (Metadata $metadata) {
                $this->assertEquals(['username' => 'doej'], $metadata->getSubContext('user'));
                $this->assertEquals(['module' => 'Introduction'], $metadata->getSubContext('custom'));
                return true;
            }));

        // Cause connector to want payload data
        $connector->expects($this->once())->method('isPayloadSet')->willReturn(false);

        /** @var ConnectorFactory|MockObject $connectorFactory */
        $connectorFactory = $this->createMock(ConnectorFactory::class);
        $connectorFactory->expects($this->once())->method('makeConnector')
            ->willreturn($connector);

        $agent = $this->makeAgent([
            'config' => new Config([ 'serviceName' => 'phpunit_1' ]),
            'user' => ['username' => 'doej'],
            'custom' => ['module' => 'Introduction'],
        ], $connectorFactory);

        $agent->send();
    }
}
