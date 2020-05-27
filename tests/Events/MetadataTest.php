<?php

namespace Nipwaayoni\Tests\Events;

use Nipwaayoni\Events\Metadata;
use Nipwaayoni\Helper\Config;
use Nipwaayoni\Tests\SchemaTestCase;

class MetadataTest extends SchemaTestCase
{
    public function schemaVersionDataProvider(): array
    {
        return [
            '6.7' => ['6.7', 'metadata.json'],
            '7.6' => ['7.6', 'metadata.json'],
            '7.8' => ['7.8', 'metadata.json'],
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
        $metadata = new Metadata([], new Config(['appName' => 'SchemaTest']));

        $this->validateObjectAgainstSchema($metadata, $schemaVersion, $schemaFile);
    }
}
