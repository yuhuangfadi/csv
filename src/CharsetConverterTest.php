<?php

/**
 * League.Csv (https://csv.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace League\Csv;

use ArrayIterator;
use Iterator;
use OutOfRangeException;
use PHPUnit\Framework\TestCase;
use function explode;
use function implode;
use function mb_convert_encoding;
use function stream_filter_register;
use function stream_get_filters;
use function strtoupper;

/**
 * @group converter
 * @coversDefaultClass \League\Csv\CharsetConverter
 */
final class CharsetConverterTest extends TestCase
{
    /**
     * @covers ::inputEncoding
     * @covers ::filterEncoding
     */
    public function testCharsetConverterTriggersException(): void
    {
        $this->expectException(OutOfRangeException::class);
        (new CharsetConverter())->inputEncoding('');
    }

    /**
     * @covers ::inputEncoding
     * @covers ::outputEncoding
     */
    public function testCharsetConverterRemainsTheSame(): void
    {
        $converter = new CharsetConverter();
        self::assertSame($converter, $converter->inputEncoding('utf-8'));
        self::assertSame($converter, $converter->outputEncoding('UtF-8'));
        self::assertNotEquals($converter->outputEncoding('iso-8859-15'), $converter);
    }

    /**
     * @covers ::convert
     * @covers ::filterEncoding
     * @covers ::encodeField
     * @covers ::inputEncoding
     * @covers ::__invoke
     */
    public function testCharsetConverterDoesNothing(): void
    {
        $converter = new CharsetConverter();
        $expected = new ArrayIterator([['a' => 'bé']]);
        /** @var array $record */
        $record = $expected[0];
        self::assertEquals($expected, $converter->convert($expected));
        self::assertEquals($record, ($converter)($record));
        self::assertNotEquals($record, ($converter->outputEncoding('utf-16'))($record));
    }

    /**
     * @covers ::convert
     * @covers ::inputEncoding
     */
    public function testCharsetConverterConvertsAnArray(): void
    {
        $expected = ['Batman', 'Superman', 'Anaïs'];
        $raw = explode(',', mb_convert_encoding(implode(',', $expected), 'iso-8859-15', 'utf-8'));
        $converter = (new CharsetConverter())
            ->inputEncoding('iso-8859-15')
            ->inputEncoding('iso-8859-15')
            ->outputEncoding('utf-8')
        ;

        /** @var array $result */
        $result = $converter->convert([$raw]);

        self::assertSame($expected, $result[0]);
    }


    /**
     * @covers ::convert
     * @covers ::inputEncoding
     */
    public function testCharsetConverterConvertsAnIterator(): void
    {
        $expected = new ArrayIterator(['Batman', 'Superman', 'Anaïs']);
        $converter = (new CharsetConverter())
            ->inputEncoding('iso-8859-15')
            ->outputEncoding('utf-8')
        ;
        self::assertInstanceOf(Iterator::class, $converter->convert($expected));
    }

    /**
     * @covers ::register
     * @covers ::getFiltername
     * @covers ::addTo
     * @covers ::onCreate
     * @covers ::filter
     */
    public function testCharsetConverterAsStreamFilter(): void
    {
        $expected = 'Batman,Superman,Anaïs';
        $raw = mb_convert_encoding($expected, 'iso-8859-15', 'utf-8');
        $csv = Reader::createFromString($raw)
            ->addStreamFilter('string.toupper');
        CharsetConverter::addTo($csv, 'iso-8859-15', 'utf-8');

        self::assertContains(CharsetConverter::FILTERNAME.'.*', stream_get_filters());
        self::assertSame(strtoupper($expected), $csv->toString());
    }

    /**
     * @covers ::onCreate
     * @covers ::filter
     */
    public function testCharsetConverterAsStreamFilterFailed(): void
    {
        $this->expectException(InvalidArgument::class);
        stream_filter_register(CharsetConverter::FILTERNAME.'.*', CharsetConverter::class);
        $expected = 'Batman,Superman,Anaïs';
        $raw = mb_convert_encoding($expected, 'iso-8859-15', 'utf-8');
        $csv = Reader::createFromString($raw)
            ->addStreamFilter('string.toupper')
            ->addStreamFilter('convert.league.csv.iso-8859-15:utf-8')
        ;
    }

    /**
     * @covers ::onCreate
     */
    public function testOnCreateFailsWithWrongFiltername(): void
    {
        $converter = new CharsetConverter();
        $converter->filtername = 'toto';
        self::assertFalse($converter->onCreate());
    }

    /**
     * @covers ::onCreate
     */
    public function testOnCreateFailedWithWrongParams(): void
    {
        $converter = new CharsetConverter();
        $converter->filtername = CharsetConverter::FILTERNAME.'.foo/bar';
        self::assertFalse($converter->onCreate());
    }

    /**
     * @covers ::convert
     * @covers ::encodeField
     *
     * @dataProvider converterProvider
     */
    public function testConvertOnlyStringField(array $record, array $expected): void
    {
        $converter = (new CharsetConverter())
            ->inputEncoding('iso-8859-15')
            ->outputEncoding('utf-8');
        /** @var array $res */
        $res = $converter->convert([$record]);

        self::assertSame($expected, $res[0]);
    }

    public function converterProvider(): array
    {
        return [
            'only numeric values' => [
                'record' => [1, 2, 3],
                'expected' => [1, 2, 3],
            ],
            'only string values' => [
                'record' => ['1', '2', '3'],
                'expected' => ['1', '2', '3'],
            ],
            'mixed values' => [
                'record' => [1, '2', 3],
                'expected' => [1, '2', 3],
            ],
            'mixed offset' => [
                'record' => [1 => 1, '2' => '2', 3 => 3],
                'expected' => [1 => 1, '2' => '2', 3 => 3],
            ],
        ];
    }
}
