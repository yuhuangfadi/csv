<?php

/**
 * League.Csv (https://csv.thephpleague.com).
 *
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license https://github.com/thephpleague/csv/blob/master/LICENSE (MIT License)
 * @version 9.2.0
 * @link    https://github.com/thephpleague/csv
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LeagueTest\Csv\Polyfill;

use League\Csv\Polyfill\EmptyEscapeParser;
use League\Csv\Reader;
use League\Csv\Stream;
use PHPUnit\Framework\TestCase;
use SplTempFileObject;
use TypeError;
use function iterator_to_array;

/**
 * @group reader
 * @coversDefaultClass League\Csv\Polyfill\EmptyEscapeParser
 */
class EmptyEscapeParserTest extends TestCase
{
    /**
     * @covers ::parse
     * @covers ::filterDocument
     */
    public function testConstructorThrowsTypeErrorWithUnknownDocument()
    {
        self::expectException(TypeError::class);
        foreach (EmptyEscapeParser::parse([]) as $record) {
        }
    }

    /**
     * @covers \League\Csv\Stream::fgets
     * @covers ::parse
     * @covers ::filterDocument
     * @covers ::parse
     * @covers ::extractFieldContent
     * @covers ::extractEnclosedFieldContent
     */
    public function testWorksWithMultiLines()
    {
        $source = <<<EOF
Year,Make,Model,Description,Price
1997,Ford,E350,"ac, abs, moon",3000.00
1999,Chevy,"Venture ""Extended Edition""","",4900.00
1999,Chevy,"Venture ""Extended Edition, Very Large""",,5000.00
1996,Jeep,Grand Cherokee,"MUST SELL!
air, moon roof, loaded",4799.00
EOF;

        $multiline = <<<EOF
MUST SELL!
air, moon roof, loaded
EOF;
        $iterator = EmptyEscapeParser::parse(Stream::createFromString($source));
        $data = iterator_to_array($iterator, false);
        self::assertCount(5, $data);
        self::assertSame($multiline, $data[4][3]);
    }

    /**
     * @covers \League\Csv\Stream::fgets
     * @covers ::parse
     * @covers ::extractFieldContent
     * @covers ::extractEnclosedFieldContent
     */
    public function testWorksWithMultiLinesWithDifferentDelimiter()
    {
        $source = <<<EOF
Year|Make|Model|Description|Price
1997|Ford|E350|'ac, abs, moon'|3000.00
1999|Chevy|'Venture ''Extended Edition'''|''|4900.00
1999|Chevy|'Venture ''Extended Edition| Very Large'''||5000.00
1996|Jeep|Grand Cherokee|'MUST SELL!
air| moon roof| loaded'|4799.00
EOF;

        $multiline = <<<EOF
MUST SELL!
air| moon roof| loaded
EOF;
        $doc = Stream::createFromString($source);
        $doc->setCsvControl('|', "'");
        $data = iterator_to_array(EmptyEscapeParser::parse($doc), false);
        self::assertCount(5, $data);
        self::assertSame($multiline, $data[4][3]);
    }

    /**
     * @covers ::parse
     * @covers ::extractFieldContent
     * @covers ::extractEnclosedFieldContent
     */
    public function testKeepEmptyLines()
    {
        $source = <<<EOF
"parent name","child name","title"


"parentA","childA","titleA"
EOF;

        $rsrc = new SplTempFileObject();
        $rsrc->fwrite($source);

        $expected = [
            ['parent name', 'child name', 'title'],
            ['parentA', 'childA', 'titleA'],
        ];

        foreach (EmptyEscapeParser::parse($rsrc) as $offset => $record) {
            self::assertSame($expected[$offset], $record);
        }
    }

    /**
     * @covers ::parse
     * @covers ::extractFieldContent
     * @covers ::extractEnclosedFieldContent
     */
    public function testNoTrimmedSpaceWithNotEncloseField()
    {
        $source = <<<EOF
Year,Make,Model,,Description,   Price
1997,  Ford  ,E350  ,ac, abs, moon,   3000.00
EOF;

        $expected = [
            ['Year', 'Make', 'Model', '', 'Description', '   Price'],
            ['1997', '  Ford  ', 'E350  ', 'ac', ' abs', ' moon', '   3000.00'],
        ];

        $stream = Stream::createFromString($source);
        foreach (EmptyEscapeParser::parse($stream) as $offset => $record) {
            self::assertSame($expected[$offset], $record);
        }
    }

    /**
     * @covers ::parse
     * @covers ::extractFieldContent
     * @covers ::extractEnclosedFieldContent
     *
     * @dataProvider invalidCsvRecordProvider
     */
    public function testHandlingInvalidCSVwithEnclosure(string $string, array $expected)
    {
        $stream = Stream::createFromString($string);
        foreach (EmptyEscapeParser::parse($stream) as $record) {
            self::assertSame($expected, $record);
        }
    }

    public function invalidCsvRecordProvider()
    {
        return [
            'enclosure inside a non-unclosed field' => [
                'string' => 'Ye"ar,Make",Model,Description,Price',
                'record' => ['Ye"ar', 'Make"', 'Model', 'Description', 'Price'],
            ],
            'enclosure at the end of a non-unclosed field' => [
                'string' => 'Year,Make,Model,Description,Price"',
                'record' => ['Year', 'Make', 'Model', 'Description', 'Price"'],
            ],
            'enclosure at the end of a record field' => [
                'string' => 'Year,Make,Model,Description,"Price',
                'record' => ['Year', 'Make', 'Model', 'Description', 'Price'],
            ],
            'enclosure started but not ended' => [
                'string' => 'Year,Make,Model,Description,"Pri"ce',
                'record' => ['Year', 'Make', 'Model', 'Description', 'Price'],
            ],
        ];
    }

    /**
     * @covers ::parse
     * @covers ::extractFieldContent
     * @covers ::extractEnclosedFieldContent
     */
    public function testDoubleEnclosure()
    {
        $str = <<<EOF
Robert;Dupont;rue du Verger, 12;…
"Michel";"Durand";" av. de la Ferme, 89 ";…
"Michel ""Michele""";"Durand";" av. de la Ferme, 89";…
"Michel;Michele";"Durand";"av. de la Ferme, 89";…
EOF;

        $expected = [
            ['Robert', 'Dupont', 'rue du Verger, 12', '…'],
            ['Michel', 'Durand', ' av. de la Ferme, 89 ', '…'],
            ['Michel "Michele"', 'Durand', ' av. de la Ferme, 89', '…'],
            ['Michel;Michele', 'Durand',  'av. de la Ferme, 89', '…'],
        ];

        $stream = Stream::createFromString($str);
        $stream->setCsvControl(';', '"');
        $records = EmptyEscapeParser::parse($stream);
        foreach ($records as $offset => $record) {
            self::assertSame($expected[$offset], $record);
        }
    }

    /**
     * @covers ::parse
     * @covers ::extractFieldContent
     * @covers ::extractEnclosedFieldContent
     */
    public function testInvalidCsvParseAsFgetcsv()
    {
        $str = '"foo"bar",foo"bar'."\r\n".'"foo"'."\r\n".'baz,bar"';
        $csv = Reader::createFromString($str);
        $fgetcsv_records = iterator_to_array($csv);
        $csv->setEscape('');
        self::assertEquals($fgetcsv_records, iterator_to_array($csv));
    }

    public function testCsvParsedAsFgetcsv($value='')
    {
        $str = <<<EOF
"foo","foo bar","boo bar baz"
  "foo"  , "foo bar" ,    "boo bar baz"
EOF;
        $expected = [
            ['foo', 'foo bar', 'boo bar baz'],
            ['foo  ', 'foo bar ', 'boo bar baz'],
        ];

        $stream = Stream::createFromString($str);
        foreach (EmptyEscapeParser::parse($stream) as $offset => $record) {
            self::assertEquals($expected[$offset], $record);
        }
    }
}
