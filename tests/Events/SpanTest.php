<?php

namespace Nipwaayoni\Tests\Events;

use Nipwaayoni\Events\EventBean;
use Nipwaayoni\Events\SampleStrategy;
use Nipwaayoni\Events\Span;
use Nipwaayoni\Events\Transaction;
use Nipwaayoni\Exception\Events\AlreadyStartedException;
use Nipwaayoni\Helper\Timer;
use Nipwaayoni\Factory\TimerFactory;
use Nipwaayoni\Helper\Timestamp;
use Nipwaayoni\Tests\SchemaTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class SpanTest extends SchemaTestCase
{
    /** @var Span  */
    private $span;

    /** @var Timer|MockObject  */
    private $timer;

    /** @var TimerFactory|MockObject  */
    private $timerFactory;

    /** @var EventBean|MockObject  */
    private $parent;

    /** @var float */
    private $timestamp;

    public function setUp(): void
    {
        parent::setUp();

        $this->timestamp = microtime(true);
        $this->timer = $this->createMock(Timer::class);
        $this->timer->method('getStartTime')
            ->willReturnCallback(function () {
                return $this->timestamp;
            });

        $this->timerFactory = $this->createMock(TimerFactory::class);
        $this->timerFactory->method('newtimer')->willReturn($this->timer);

        $this->parent = $this->createMock(EventBean::class);
        $this->parent->method('getId')->willReturn('123');
        $this->parent->method('getTraceId')->willReturn('456');

        $this->span = new Span('MySpan', $this->parent, $this->timerFactory);
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
        $this->validateObjectAgainstSchema($this->span, $schemaVersion, $schemaFile);
    }

    public function testCanOnlyBeStartedOnce(): void
    {
        $this->span->start();

        $this->expectException(AlreadyStartedException::class);

        $this->span->start();
    }

    /**
     * @param float|null $startTime
     * @throws \Nipwaayoni\Exception\Timer\AlreadyRunningException
     * @dataProvider spanStartTimes
     */
    public function testPresentsCorrectTimestampAndDurationInJson(float $startTime = null): void
    {
        $this->timestamp = 1591785019.5996;
        $duration = 2.34;

        $this->timer->expects($this->once())->method('getDurationInMilliseconds')
            ->willReturn($duration);

        $this->timerFactory->expects($this->once())->method('newTimer')
            ->with($this->equalTo($startTime))
            ->willReturn($this->timer);

        $this->span->start($startTime);
        $this->span->stop();

        $payload = json_decode(json_encode($this->span), true);

        $timestamp = new Timestamp($this->timestamp);

        $this->assertEquals(floor($timestamp->asMicroSeconds()), $payload['span']['timestamp']);
        $this->assertEquals($duration, $payload['span']['duration']);
    }

    /**
     * @throws \Nipwaayoni\Exception\Timer\AlreadyRunningException
     *
     * @dataProvider spanStartTimes
     */
    public function testUsesCorrectStartTimeForTimer(float $startTime = null): void
    {
        $this->timerFactory->expects($this->once())->method('newTimer')
            ->with($this->equalTo($startTime))
            ->willReturn($this->timer);

        $this->span->start($startTime);
        $this->span->stop();
    }

    public function spanStartTimes(): array
    {
        return [
            'null start time' => [null],
            'set start time' => [microtime(true)],
        ];
    }

    /**
     * @dataProvider isSampledChecks
     */
    public function testIsSampledIsReflectsParentStrategy(SampleStrategy $strategy): void
    {
        $parent = new Transaction('MyParent', []);
        $parent->sampleStrategy($strategy);

        $this->span = new Span('MySpan', $parent, $this->timerFactory);

        $this->assertEquals($strategy->sampleEvent(), $this->span->isSampled());
    }

    public function isSampledChecks(): array
    {
        return [
            'include' => [$this->makeIncludeStrategy()],
            'exclude' => [$this->makeExcludeStrategy()],
        ];
    }
}
