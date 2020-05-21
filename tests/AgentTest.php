<?php

namespace Nipwaayoni\Tests;

use Nipwaayoni\Agent;
use Nipwaayoni\Stores\TransactionsStore;

/**
 * Test Case for @see \Nipwaayoni\Agent
 */
final class AgentTest extends TestCase
{

    /**
     * @covers \Nipwaayoni\Agent::__construct
     * @covers \Nipwaayoni\Agent::startTransaction
     * @covers \Nipwaayoni\Agent::stopTransaction
     * @covers \Nipwaayoni\Agent::getTransaction
     */
    public function testStartAndStopATransaction()
    {
        $agent = new Agent([ 'appName' => 'phpunit_1', 'active' => false, ]);

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
        $agent = new Agent([ 'appName' => 'phpunit_1', 'active' => false, ]);

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
        $agent = new Agent([ 'appName' => 'phpunit_x', 'active' => false, ]);

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
        $agent = new Agent([ 'appName' => 'phpunit_2', 'active' => false, ]);

        $this->expectException(\Nipwaayoni\Exception\Transaction\UnknownTransactionException::class);

        // Stop an unstarted Transaction and let it go boom!
        $agent->stopTransaction('unknown');
    }
}
