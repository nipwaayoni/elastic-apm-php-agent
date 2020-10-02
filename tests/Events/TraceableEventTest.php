<?php

namespace Nipwaayoni\Tests\Events;

use GuzzleHttp\Psr7\Request;
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
        $headerName = 'HTTP_' . strtoupper($header);
        $traceParentId = sprintf('00-%s-%s-00', $expectedTraceId, $expectedParentId);

        $_SERVER[$headerName] = $traceParentId;

        $event = new TraceableEvent([]);

        $this->assertEquals($expectedParentId, $event->getParentId());
        $this->assertEquals($expectedTraceId, $event->getTraceId());

        unset($_SERVER[$headerName]);
    }

    public function traceParentChecks(): array
    {
        return [
            'elastic apm header' => [
                'elastic_apm_traceparent',
                '0372560873a826f5',
                '020981806b86a38ddc7998fe0c2b2c75'
            ],
            'www header' => [
                'traceparent',
                '0372560873a826f6',
                '020981806b86a38ddc7998fe0c2b2c76'
            ],
        ];
    }

    public function testProvidesTraceParentHeaderAsArray(): void
    {
        $event = new TraceableEvent([]);

        $header = $event->traceHeaderAsArray();

        $this->assertArrayHasKey(TraceableEvent::TRACEPARENT_HEADER_NAME, $header);
    }

    public function testProvidesTraceParentHeaderAsString(): void
    {
        $event = new TraceableEvent([]);

        $header = $event->traceHeaderAsString();

        $this->assertTrue(strpos($header, TraceableEvent::TRACEPARENT_HEADER_NAME) === 0);
    }

    public function testAddsTraceParentHeaderAsStringHttpRequest(): void
    {
        $originalRequest = new Request('GET', 'https://example.com');
        $event = new TraceableEvent([]);

        $request = $event->addTraceHeaderToRequest($originalRequest);

        $this->assertTrue($request->hasHeader(TraceableEvent::TRACEPARENT_HEADER_NAME));
    }
}
