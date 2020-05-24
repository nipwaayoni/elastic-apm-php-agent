<?php

namespace Nipwaayoni\Tests\Events;

use Nipwaayoni\Events\Transaction;
use Nipwaayoni\Tests\SchemaTestCase;

/**
 * Test Case for @see \Nipwaayoni\Events\Transaction
 */
final class TransactionTest extends SchemaTestCase
{
    public function schemaVersionDataProvider(): array
    {
        return [
            // TODO add support for multiple schema versions
            // '6.7 v1 transaction' => ['6.7', 'transactions/v1_transaction.json'],
            '6.7 v2 transaction' => ['6.7', 'transactions/v2_transaction.json'],
            '7.6' => ['7.6', 'transactions/transaction.json'],
            '7.8' => ['7.8', 'transactions/transaction.json'],
        ];
    }

    public function testTestsSupportedSchemaVersions(): void
    {
        $this->assertSupportedSchemasAreTested($this->findUntestedSupportedSchemaVersions());
    }

    /**
     * @dataProvider schemaVersionDataProvider
     * @param string $schemaVersion
     * @param string $schemaFile
     * @throws \Nipwaayoni\Exception\MissingAppNameException
     */
    public function testProducesValidJson(string $schemaVersion, string $schemaFile): void
    {
        $transaction = new Transaction('MyTransaction', []);

        $this->validateObjectAgainstSchema($transaction, $schemaVersion, $schemaFile);
    }

    /**
     * @covers \Nipwaayoni\Events\EventBean::getId
     * @covers \Nipwaayoni\Events\EventBean::getTraceId
     * @covers \Nipwaayoni\Events\Transaction::getTransactionName
     * @covers \Nipwaayoni\Events\Transaction::setTransactionName
     */
    public function testParentConstructor()
    {
        $name = 'testerus-grandes';
        $transaction = new Transaction($name, []);

        $this->assertEquals($name, $transaction->getTransactionName());
        $this->assertNotNull($transaction->getId());
        $this->assertNotNull($transaction->getTraceId());
        $this->assertNotNull($transaction->getTimestamp());

        $now = round(microtime(true) * 1000000);
        $this->assertGreaterThanOrEqual($transaction->getTimestamp(), $now);
    }

    /**
     * @depends testParentConstructor
     *
     * @covers \Nipwaayoni\Events\EventBean::setParent
     * @covers \Nipwaayoni\Events\EventBean::getTraceId
     * @covers \Nipwaayoni\Events\EventBean::ensureGetTraceId
     */
    public function testParentReference()
    {
        $parent = new Transaction('parent', []);
        $child  = new Transaction('child', []);
        $child->setParent($parent);

        $arr = json_decode(json_encode($child), true);

        $this->assertEquals($arr['transaction']['id'], $child->getId());
        $this->assertEquals($arr['transaction']['parent_id'], $parent->getId());
        $this->assertEquals($arr['transaction']['trace_id'], $parent->getTraceId());
        $this->assertEquals($child->getTraceId(), $parent->getTraceId());
    }
}
