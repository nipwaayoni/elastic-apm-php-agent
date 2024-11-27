<?php

namespace Nipwaayoni\Tests\Events;

use Nipwaayoni\Events\Error;
use Nipwaayoni\Events\Transaction;
use Nipwaayoni\Tests\SchemaTestCase;

class ErrorTest extends SchemaTestCase
{
    public static function schemaVersionDataProvider(): array
    {
        return [
            // TODO add support for multiple schema versions
            // '6.7 v1 errors' => ['6.7', 'errors/v1_error.json'],
            '6.7 v2 errors' => ['6.7', 'errors/v2_error.json'],
            '7.6' => ['7.6', 'errors/error.json'],
            '7.8' => ['7.8', 'errors/error.json'],
            '8.3' => ['8.3', 'v2/error.json'],
        ];
    }

    /**
     * Tests that the object output by this class is tested against the supported APM schema versions.
     * The abstract method is implemented here for transparency during the PHPUnit test run and
     * reporting.
     */
    public function testTestsSupportedSchemaVersions(): void
    {
        $this->assertSupportedSchemasAreTested($this->findUntestedSupportedSchemaVersions());
    }

    /**
     * @dataProvider schemaVersionDataProvider
     * @param string $schemaVersion
     * @param string $schemaFile
     */
    public function testProducesValidJson(string $schemaVersion, string $schemaFile): void
    {
        $error = new Error(new \Exception(), []);

        $this->validateObjectAgainstSchema($error, $schemaVersion, $schemaFile);
    }

    public function testErrorContainsTransactionData(): void
    {
        $transaction  = new Transaction('active-transaction', []);
        $transaction->setMeta(['type' => 'request']);

        $error = new Error(new \Exception(), [], $transaction);

        $data = json_decode(json_encode($error), true);

        $this->assertEquals('active-transaction', $data['error']['transaction']['name']);
        $this->assertTrue($data['error']['transaction']['sampled']);
        $this->assertEquals('request', $data['error']['transaction']['type']);
    }

    public function testErrorDoesNotContainTransactionObjectWithoutParentTransaction(): void
    {
        $error = new Error(new \Exception(), []);

        $data = json_decode(json_encode($error), true);

        $this->assertArrayNotHasKey('transaction', $data['error']);
    }
}
