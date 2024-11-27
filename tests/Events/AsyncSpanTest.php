<?php

namespace Nipwaayoni\Tests\Events;

use Nipwaayoni\Events\AsyncSpan;
use Nipwaayoni\Events\EventBean;
use Nipwaayoni\Helper\Timer;
use Nipwaayoni\Factory\TimerFactory;
use Nipwaayoni\Tests\SchemaTestCase;

class AsyncSpanTest extends SchemaTestCase
{
    /** @var AsyncSpan  */
    private $span;

    /** @var float */
    private $timestamp;

    public function setUp(): void
    {
        parent::setUp();

        $this->timestamp = microtime(true);
        $timer = $this->createMock(Timer::class);
        $timer->method('getStartTime')
            ->willReturnCallback(function () {
                return $this->timestamp;
            });

        $timerFactory = $this->createMock(TimerFactory::class);
        $timerFactory->method('newtimer')->willReturn($timer);

        $parent = $this->createMock(EventBean::class);
        $parent->method('getId')->willReturn('123');
        $parent->method('getTraceId')->willReturn('456');

        $this->span = new AsyncSpan('MySpan', $parent, $timerFactory);
    }

    public static function schemaVersionDataProvider(): array
    {
        return [
            // TODO add support for multiple schema versions
            // '6.7 v1 span' => ['6.7', 'spans/v1_span.json'],
            '6.7 v2 span' => ['6.7', 'spans/v2_span.json'],
            '7.6' => ['7.6', 'spans/span.json'],
            '7.8' => ['7.8', 'spans/span.json'],
            '8.3' => ['8.3', 'v2/span.json'],
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
        $this->validateObjectAgainstSchema($this->span, $schemaVersion, $schemaFile);
    }

    public function testSpanIsNotSync(): void
    {
        $payload = json_decode(json_encode($this->span), true);

        $this->assertFalse($payload['span']['sync']);
    }
}
