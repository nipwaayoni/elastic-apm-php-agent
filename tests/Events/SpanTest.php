<?php

namespace Nipwaayoni\Tests\Events;

use Nipwaayoni\Events\EventBean;
use Nipwaayoni\Events\Span;
use Nipwaayoni\Helper\Timer;
use Nipwaayoni\Tests\SchemaTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class SpanTest extends SchemaTestCase
{
    /** @var Timer|MockObject  */
    private $timer;

    public function setUp(): void
    {
        parent::setUp();

        $this->timer = $this->createMock(Timer::class);
    }

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

    /**
     * @throws \Nipwaayoni\Exception\Timer\AlreadyRunningException
     *
     * @dataProvider spanStartTimes
     */
    public function testUsesCorrectStartTime(float $startTime = null): void
    {
        /** @var EventBean|MockObject $parent */
        $parent = $this->createMock(EventBean::class);
        $parent->method('getId')->willReturn('123');
        $parent->method('getTraceId')->willReturn('456');

        $this->timer->expects($this->once())->method('stop');

        $span = $this->makeTestSpan('MySpan', $parent, $startTime);
        $span->start($startTime);
        $span->stop();
    }

    public function spanStartTimes(): array
    {
        return [
            'null start time' => [null],
            'set start time' => [microtime(true)],
        ];
    }

    private function makeTestSpan(string $name, EventBean $parent, float $expectedStart = null): Span
    {
        // The anonymous class does not have access to the members of the containing class, so give
        // it a callable which will carry the necessary scope.
        $timer = $this->timer;

        $createTimer = function (float $start = null) use ($timer, $expectedStart) {
            $this->assertEquals($expectedStart, $start);
            return $timer;
        };

        return new class($name, $parent, $createTimer) extends Span {
            /**
             * @var callable
             */
            private $createTimerFunction;

            public function __construct(string $name, EventBean $parent, callable $createTimerFunction)
            {
                parent::__construct($name, $parent);

                $this->createTimerFunction = $createTimerFunction;
            }

            protected function createTimer(float $startTime = null): Timer
            {
                $function = $this->createTimerFunction;
                return $function($startTime);
            }
        };
    }
}
