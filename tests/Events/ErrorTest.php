<?php

namespace Nipwaayoni\Tests\Events;

use Nipwaayoni\Events\Error;
use Nipwaayoni\Tests\SchemaTestCase;

class ErrorTest extends SchemaTestCase
{
    public function schemaVersionDataProvider(): array
    {
        return [
            // TODO add support for multiple schema versions
            // '6.7 v1 errors' => ['6.7', 'errors/v1_error.json'],
            '6.7 v2 errors' => ['6.7', 'errors/v2_error.json'],
            '7.6' => ['7.6', 'errors/error.json'],
            '7.8' => ['7.8', 'errors/error.json'],
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
}
