<?php

namespace Nipwaayoni\Tests\Events;

use Nipwaayoni\Events\Metadata;
use Nipwaayoni\Config;
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
     * @throws \Nipwaayoni\Exception\MissingServiceNameException
     */
    public function testProducesValidJson(string $schemaVersion, string $schemaFile): void
    {
        $agentMetadata = ['name' => 'My Agent', 'version' => '1.0.0'];
        $metadata = new Metadata([], new Config(['serviceName' => 'SchemaTest']), $agentMetadata);

        $this->validateObjectAgainstSchema($metadata, $schemaVersion, $schemaFile);
    }
}
