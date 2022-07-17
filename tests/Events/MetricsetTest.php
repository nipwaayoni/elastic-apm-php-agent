<?php

namespace Nipwaayoni\Tests\Events;

use Nipwaayoni\Events\Metricset;
use Nipwaayoni\Tests\SchemaTestCase;

class MetricsetTest extends SchemaTestCase
{
    public function schemaVersionDataProvider(): array
    {
        return [
            // TODO add support for multiple schema versions
            // '6.7 v1 metricset' => ['6.7', 'metricsets/v1_metricset.json'],
            '6.7 v2 metricset' => ['6.7', 'metricsets/v2_metricset.json'],
            '7.6' => ['7.6', 'metricsets/metricset.json'],
            '7.8' => ['7.8', 'metricsets/metricset.json'],
            '8.3' => ['8.3', 'v2/metricset.json'],
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
        $metricset = new Metricset(['my.metric' => 123]);

        $this->validateObjectAgainstSchema($metricset, $schemaVersion, $schemaFile);
    }
}
