<?php

namespace LeagueTest\Csv;

use League\Csv\BOM;
use League\Csv\Exception\InvalidArgumentException;
use League\Csv\Exception\RuntimeException;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;
use LeagueTest\Csv\Lib\FilterReplace;
use LogicException;
use PHPUnit\Framework\TestCase;
use SplFileObject;
use SplTempFileObject;

/**
 * @group csv
 */
class CsvTest extends TestCase
{
    private $csv;

    private $expected = [
        ['john', 'doe', 'john.doe@example.com'],
        ['jane','doe','jane.doe@example.com'],
    ];

    public function setUp()
    {
        $tmp = new SplTempFileObject();
        foreach ($this->expected as $row) {
            $tmp->fputcsv($row);
        }

        $this->csv = Reader::createFromFileObject($tmp);
    }

    public function tearDown()
    {
        $this->csv = null;
    }

    public function testCreateFromPathThrowsRuntimeException()
    {
        $this->expectException(RuntimeException::class);
        Reader::createFromPath(__DIR__.'/foo/bar', 'r');
    }

    public function testCreateFromStreamWithInvalidParameter()
    {
        $this->expectException(InvalidArgumentException::class);
        $path = __DIR__.'/data/foo.csv';
        Reader::createFromStream($path);
    }

    public function testCloningIsForbidden()
    {
        $this->expectException(LogicException::class);
        clone $this->csv;
    }

    /**
     * @runInSeparateProcess
     */
    public function testOutputSize()
    {
        $this->assertSame(60, $this->csv->output(__DIR__.'/data/test.csv'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testOutputHeaders()
    {
        if (!function_exists('xdebug_get_headers')) {
            $this->markTestSkipped();
        }
        $this->csv->output('test.csv');
        $headers = \xdebug_get_headers();

        // Due to the variety of ways the xdebug expresses Content-Type of text files,
        // we cannot count on complete string matching.
        $this->assertContains('content-type: text/csv', strtolower($headers[0]));
        $this->assertSame($headers[1], 'content-transfer-encoding: binary');
        $this->assertSame($headers[2], 'content-disposition: attachment; filename="test.csv"');
    }

    public function testToString()
    {
        $expected = "john,doe,john.doe@example.com\njane,doe,jane.doe@example.com\n";
        $this->assertSame($expected, (string) $this->csv);
    }

    public function testDelimeter()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->csv->setDelimiter('o');
        $this->assertSame('o', $this->csv->getDelimiter());
        $this->csv->setDelimiter('foo');
    }

    public function testBOMSettings()
    {
        $this->assertSame('', $this->csv->getOutputBOM());
        $this->csv->setOutputBOM(BOM::UTF8);
        $this->assertSame(BOM::UTF8, $this->csv->getOutputBOM());
        $this->csv->setOutputBOM('');
        $this->assertSame('', $this->csv->getOutputBOM());
    }

    public function testAddBOMSequences()
    {
        $this->csv->setOutputBOM(BOM::UTF8);
        $expected = chr(239).chr(187).chr(191).'john,doe,john.doe@example.com'.PHP_EOL
            .'jane,doe,jane.doe@example.com'.PHP_EOL;
        $this->assertSame($expected, (string) $this->csv);
    }

    public function testGetBomOnInputWithNoBOM()
    {
        $expected = 'john,doe,john.doe@example.com'.PHP_EOL
            .'jane,doe,jane.doe@example.com'.PHP_EOL;
        $reader = Reader::createFromString($expected);
        $this->assertNotContains(BOM::UTF8, (string) $reader);
    }

    public function testChangingBOMOnOutput()
    {
        $text = 'john,doe,john.doe@example.com'.PHP_EOL
            .'jane,doe,jane.doe@example.com'.PHP_EOL;
        $reader = Reader::createFromString(BOM::UTF32_BE.$text);
        $reader->setOutputBOM(BOM::UTF8);
        $this->assertSame(BOM::UTF8.$text, (string) $reader);
    }

    public function testDetectDelimiterList()
    {
        $this->assertSame([',' => 4], $this->csv->fetchDelimitersOccurrence([',']));
    }

    public function testEscape()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->csv->setEscape('o');
        $this->assertSame('o', $this->csv->getEscape());

        $this->csv->setEscape('foo');
    }

    public function testEnclosure()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->csv->setEnclosure('o');
        $this->assertSame('o', $this->csv->getEnclosure());

        $this->csv->setEnclosure('foo');
    }

    /**
     * @dataProvider appliedFlagsProvider
     */
    public function testAppliedFlags($flag, $fetch_count)
    {
        $path = __DIR__.'/data/tmp.txt';
        $obj  = new SplFileObject($path, 'w+');
        $obj->fwrite("1st\n2nd\n");
        $obj->setFlags($flag);
        $reader = Reader::createFromFileObject($obj);
        $this->assertCount($fetch_count, (new Statement())->process($reader)->fetchAll());
        $reader = null;
        $obj = null;
        unlink($path);
    }

    public function appliedFlagsProvider()
    {
        return [
            'NONE' => [0, 2],
            'DROP_NEW_LINE' => [SplFileObject::READ_AHEAD | SplFileObject::DROP_NEW_LINE, 2],
            'READ_AHEAD' => [SplFileObject::READ_AHEAD, 2],
            'SKIP_EMPTY' => [SplFileObject::SKIP_EMPTY, 2],
            'READ_AHEAD|DROP_NEW_LINE' => [SplFileObject::READ_AHEAD | SplFileObject::DROP_NEW_LINE, 2],
            'READ_AHEAD|SKIP_EMPTY' => [SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY, 2],
            'DROP_NEW_LINE|SKIP_EMPTY' => [SplFileObject::DROP_NEW_LINE | SplFileObject::SKIP_EMPTY, 2],
            'READ_AHEAD|DROP_NEW_LINE|SKIP_EMPTY' => [SplFileObject::READ_AHEAD | SplFileObject::DROP_NEW_LINE | SplFileObject::SKIP_EMPTY, 2],
        ];
    }

    public function testAddStreamFilter()
    {
        $csv = Reader::createFromPath(__DIR__.'/data/foo.csv');
        $csv->addStreamFilter('string.rot13');
        $csv->addStreamFilter('string.tolower');
        $csv->addStreamFilter('string.toupper');
        foreach ($csv as $row) {
            $this->assertSame($row, ['WBUA', 'QBR', 'WBUA.QBR@RKNZCYR.PBZ']);
        }
    }

    public function testFailedAddStreamFilter()
    {
        $this->expectException(LogicException::class);
        $csv = Writer::createFromFileObject(new SplTempFileObject());
        $this->assertFalse($csv->supportsStreamFilter());
        $csv->addStreamFilter('string.toupper');
    }

    public function testStreamFilterDetection()
    {
        $filtername = 'string.toupper';
        $csv = Reader::createFromPath(__DIR__.'/data/foo.csv');
        $this->assertFalse($csv->hasStreamFilter($filtername));
        $csv->addStreamFilter($filtername);
        $this->assertTrue($csv->hasStreamFilter($filtername));
    }

    public function testClearAttachedStreamFilters()
    {
        $path = __DIR__.'/data/foo.csv';
        $csv = Reader::createFromPath($path);
        $csv->addStreamFilter('string.toupper');
        $this->assertContains('JOHN', (string) $csv);
        $csv = Reader::createFromPath($path);
        $this->assertNotContains('JOHN', (string) $csv);
    }

    public function testRemoveStreamFilters()
    {
        $csv = Reader::createFromPath(__DIR__.'/data/foo.csv');
        $this->assertFalse($csv->hasStreamFilter('string.tolower'));
    }

    public function testSetStreamFilterWriterNewLine()
    {
        stream_filter_register(FilterReplace::FILTER_NAME.'*', FilterReplace::class);
        $csv = Writer::createFromPath(__DIR__.'/data/newline.csv');
        $csv->addStreamFilter(FilterReplace::FILTER_NAME."\r\n:\n");
        $csv->insertOne([1, 'two', 3, "new\r\nline"]);
        $this->assertContains("1,two,3,\"new\nline\"", (string) $csv);
    }
}
