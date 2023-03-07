<?php

namespace Nipwaayoni\Tests\Stores;

use Nipwaayoni\Stores\TransactionsStore;
use Nipwaayoni\Events\Transaction;
use Nipwaayoni\Tests\TestCase;

/**
 * Test Case for @see \Nipwaayoni\Stores\TransactionsStore
 */
final class TransactionsStoreTest extends TestCase
{
    /**
     * @covers \Nipwaayoni\Stores\TransactionsStore::register
     * @covers \Nipwaayoni\Stores\TransactionsStore::fetch
     */
    public function testTransactionRegistrationAndFetch()
    {
        $store = new TransactionsStore();
        $name  = 'test';
        $trx   = new Transaction($name, []);

        // Must be Empty
        $this->assertTrue($store->isEmpty());

        // Store the Transaction and fetch it then
        $store->register($trx);
        $proof = $store->fetch($name);

        // We should get the Same!
        $this->assertEquals($trx, $proof);
        $this->assertNotNull($proof);

        // Must not be Empty
        $this->assertFalse($store->isEmpty());
    }

    /**
     * @depends testTransactionRegistrationAndFetch
     *
     * @covers \Nipwaayoni\Stores\TransactionsStore::register
     */
    public function testDuplicateTransactionRegistration()
    {
        $store = new TransactionsStore();
        $name  = 'test';
        $trx   = new Transaction($name, []);

        $this->expectException(\Nipwaayoni\Exception\Transaction\DuplicateTransactionNameException::class);

        // Store the Transaction again to force an Exception
        $store->register($trx);
        $store->register($trx);
    }

    /**
     * @depends testTransactionRegistrationAndFetch
     *
     * @covers \Nipwaayoni\Stores\TransactionsStore::fetch
     */
    public function testFetchUnknownTransaction()
    {
        $store = new TransactionsStore();
        $this->assertNull($store->fetch('unknown'));
    }
}
