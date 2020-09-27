<?php

namespace Nipwaayoni\Tests\Events;

use Nipwaayoni\Events\TraceableEvent;
use Nipwaayoni\Events\Transaction;
use Nipwaayoni\Tests\TestCase;

class TraceableEventTest extends TestCase
{
    public function testGeneratesNewTraceIdWithOutParentEvent(): void
    {
        $event = new TraceableEvent([]);

        $this->assertNotNull($event->getTraceId());
    }

    public function testUsesParentTraceIdWithParentEvent(): void
    {
        $parent = new Transaction('My transaction', []);

        $event = new TraceableEvent([], $parent);

        $this->assertEquals($parent->getTraceid(), $event->getTraceId());
    }

    /**
     * @throws \Exception
     *
     * @dataProvider traceParentChecks
     */
    public function testUsesTraceParentWhenHttpHeaderIsPresent(string $header, string $expectedParentId, string $expectedTraceId): void
    {
        $traceParentId = sprintf('00-%s-%s-00', $expectedTraceId, $expectedParentId);

        $_SERVER[$header] = $traceParentId;

        $event = new TraceableEvent([]);

        $this->assertEquals($expectedParentId, $event->getParentId());
        $this->assertEquals($expectedTraceId, $event->getTraceId());

        unset($_SERVER[$header]);
    }

    public function traceParentChecks(): array
    {
        return [
            'elastic apm header' => [
                'HTTP_ELASTIC_APM_TRACEPARENT',
                '0372560873a826f5',
                '020981806b86a38ddc7998fe0c2b2c75'
            ],
            'www header' => [
                'HTTP_TRACEPARENT',
                '0372560873a826f6',
                '020981806b86a38ddc7998fe0c2b2c76'
            ],
        ];
    }
}
