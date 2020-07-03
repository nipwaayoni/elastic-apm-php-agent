<?php

namespace Nipwaayoni\Tests\Helper;

use Nipwaayoni\Helper\Timestamp;
use Nipwaayoni\Tests\TestCase;

class TimestampTest extends TestCase
{
    public function testIsCreatedWithCurrentMicrotime(): void
    {
        $before = new Timestamp(microtime(true));
        usleep(1000);
        $timestamp = new Timestamp();
        usleep(1000);
        $after = new Timestamp(microtime(true));

        $this->assertGreaterThan($before->asMicroSeconds(), $timestamp->asMicroSeconds());
        $this->assertLessThan($after->asMicroSeconds(), $timestamp->asMicroSeconds());
    }

    public function testIsSerializedToExpectedJsonValue(): void
    {
        $startTime = 1591785019.5996;
        $expected = (int) floor($startTime * Timestamp::MICROTIME_MULTIPLIER);

        $timestamp = new Timestamp($startTime);
        $postSerialize = json_decode(json_encode($timestamp), true);

        $this->assertEquals($expected, $postSerialize);
    }
}
