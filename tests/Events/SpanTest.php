<?php

namespace Nipwaayoni\Tests\Events;

use Nipwaayoni\Events\EventBean;
use Nipwaayoni\Events\Span;
use Nipwaayoni\Helper\Timer;
use Nipwaayoni\Tests\SchemaTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class SpanTest extends SchemaTestCase
{
    public function schemaVersionDataProvider(): array
    {
        return [
            // TODO add support for multiple schema versions
            // '6.7 v1 span' => ['6.7', 'spans/v1_span.json'],
            '6.7 v2 span' => ['6.7', 'spans/v2_span.json'],
            '7.6' => ['7.6', 'spans/span.json'],
            '7.8' => ['7.8', 'spans/span.json'],
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
        /** @var EventBean|MockObject $parent */
        $parent = $this->createMock(EventBean::class);
        $parent->method('getId')->willReturn('123');
        $parent->method('getTraceId')->willReturn('456');

        $span = new Span('MySpan', $parent);

        $this->validateObjectAgainstSchema($span, $schemaVersion, $schemaFile);
    }

    public function testUsesProvidedTimer(): void
    {
        /** @var EventBean|MockObject $parent */
        $parent = $this->createMock(EventBean::class);
        $parent->method('getId')->willReturn('123');
        $parent->method('getTraceId')->willReturn('456');

        $timer = $this->createMock(Timer::class);
        $timer->expects($this->once())->method('stop');

        $span = new Span('MySpan', $parent);
        $span->startWithTimer($timer);
        $span->stop();
    }
}
