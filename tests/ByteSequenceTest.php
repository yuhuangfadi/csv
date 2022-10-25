<?php

/**
 * League.Csv (https://csv.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LeagueTest\Csv;

use League\Csv\ByteSequence;
use PHPUnit\Framework\TestCase;
use function chr;
use function League\Csv\bom_match;

/**
 * @group csv
 */
class ByteSequenceTest extends TestCase
{
    /**
     * @dataProvider ByteSequenceMatchProvider
     * @param string $str
     * @param string $expected
     * @covers League\Csv\bom_match
     */
    public function testByteSequenceMatch($str, $expected)
    {
        self::assertSame($expected, bom_match($str));
    }

    public function ByteSequenceMatchProvider(): array
    {
        return [
            'empty string' => [
                'sequence' => '',
                'expected' => '',
            ],
            'random string' => [
                'sequence' => 'foo bar',
                'expected' => '',
            ],
            'UTF8 BOM sequence' => [
                'sequence' => chr(239).chr(187).chr(191),
                'expected' => ByteSequence::BOM_UTF8,
            ],
            'UTF8 BOM sequence at the start of a text' => [
                'sequence' => ByteSequence::BOM_UTF8.'The quick brown fox jumps over the lazy dog',
                'expected' => chr(239).chr(187).chr(191),
            ],
            'UTF8 BOM sequence inside a text' => [
                'sequence' => 'The quick brown fox '.ByteSequence::BOM_UTF8.' jumps over the lazy dog',
                'expected' => '',
            ],
            'UTF32 LE BOM sequence' => [
                'sequence' => chr(255).chr(254).chr(0).chr(0),
                'expected' => ByteSequence::BOM_UTF32_LE,
            ],
        ];
    }
}
