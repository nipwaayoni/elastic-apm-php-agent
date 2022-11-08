<?php

namespace Nipwaayoni\Tests\Helper;

use Nipwaayoni\Helper\Encoding;
use Nipwaayoni\Tests\TestCase;

/**
 * Test Case for @see \Nipwaayoni\Helper\Encoding
 */
final class EncodingTest extends TestCase
{

    /**
     * @covers \Nipwaayoni\Helper\Encoding::keywordField
     */
    public function testShortInput()
    {
        $input = "abcdefghijklmnopqrstuvwxyz1234567890";

        $this->assertEquals($input, Encoding::keywordField($input));
    }

    /**
     * @covers \Nipwaayoni\Helper\Encoding::keywordField
     */
    public function testLongInput()
    {
        $input = str_repeat("abc123", 200);
        $output = str_repeat("abc123", 170) . 'abc' . '…';

        $this->assertEquals($output, Encoding::keywordField($input));
    }

    /**
     * @covers \Nipwaayoni\Helper\Encoding::keywordField
     */
    public function testLongMultibyteInput()
    {
        $input = str_repeat("中国日本韓国合衆国", 200);
        $output = str_repeat("中国日本韓国合衆国", 113) . '中国日本韓国' . '…';

        $this->assertEquals($output, Encoding::keywordField($input));
    }

    /**
     * @dataProvider emptyInputChecks
     * @covers \Nipwaayoni\Helper\Encoding::keywordField
     */
    public function testEmptyInput($input)
    {
        $this->assertEquals($input, Encoding::keywordField($input));
    }

    public function emptyInputChecks(): array
    {
        return [
            'empty string' => [''],
            'null' => [null],
        ];
    }
}
