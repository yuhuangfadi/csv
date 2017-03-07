<?php

namespace League\Csv\test;

use PHPUnit_Framework_TestCase;
use SplTempFileObject;
use League\Csv\Reader;

/**
 * @group reader
 */
class ReaderTest extends PHPUnit_Framework_TestCase
{
    private $csv;

    private $expected = [
        ['john', 'doe', 'john.doe@example.com'],
        ['jane', 'doe', 'jane.doe@example.com'],
    ];

    public function setUp()
    {
        $csv = new SplTempFileObject;
        foreach ($this->expected as $row) {
            $csv->fputcsv($row);
        }

        $this->csv = new Reader($csv);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetLimit()
    {
        $this->csv->setLimit(1);
        $this->assertCount(1, $this->csv->fetchAll());
        $this->csv->setLimit(-4);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetOffset()
    {
        $this->csv->setOffset(1);
        $this->assertCount(1, $this->csv->fetchAll());

        $this->csv->setOffset('toto');
    }

    public function testIntervalLimitTooLong()
    {
        $this->csv->setOffset(1);
        $this->csv->setLimit(10);
        $this->assertSame([['jane', 'doe', 'jane.doe@example.com']], $this->csv->fetchAll());
    }

    public function testInterval()
    {
        $this->csv->setOffset(1);
        $this->csv->setLimit(1);
        $this->assertCount(1, $this->csv->fetchAll());
    }

    public function testFilter()
    {
        $func = function ($row) {
            return ! in_array('jane', $row);
        };
        $this->csv->setFilter($func);

        $this->assertCount(1, $this->csv->fetchAll());

        $func2 = function ($row) {
            return ! in_array('john', $row);
        };
        $this->csv->addFilter($func2);
        $this->csv->addFilter($func);

        $this->assertCount(0, $this->csv->fetchAll());

        $this->csv->addFilter($func2);
        $this->csv->addFilter($func);
        $this->assertTrue($this->csv->hasFilter($func2));
        $this->csv->removeFilter($func2);
        $this->assertFalse($this->csv->hasFilter($func2));

        $this->assertCount(1, $this->csv->fetchAll());
    }

    public function testSortBy()
    {
        $func = function ($rowA, $rowB) {
            return strcmp($rowA[0], $rowB[0]);
        };
        $this->csv->setSortBy($func);
        $this->assertSame(array_reverse($this->expected), $this->csv->fetchAll());

        $this->csv->addSortBy($func);
        $this->csv->addSortBy($func);
        $this->csv->removeSortBy($func);
        $this->assertTrue($this->csv->hasSortBy($func));
        $this->assertSame(array_reverse($this->expected), $this->csv->fetchAll());
    }

    public function testFetchAll()
    {
        $func = function ($value) {
            return array_map('strtoupper', $value);
        };

        $this->assertSame($this->expected, $this->csv->fetchAll());
        $this->assertSame(array_map($func, $this->expected), $this->csv->fetchAll($func));
    }

    public function testFetchAssoc()
    {
        $keys = ['firstname', 'lastname', 'email'];
        $res = $this->csv->fetchAssoc($keys);
        foreach ($res as $index => $row) {
            $this->assertSame($keys, array_keys($row));
            $this->assertSame($this->expected[$index], array_values($row));
        }
    }

    public function testFetchAssocCallback()
    {
        $keys = ['firstname', 'lastname', 'email'];
        $func = function ($value) {
            return array_map('strtoupper', $value);
        };
        $res = $this->csv->fetchAssoc($keys, $func);
        foreach ($res as $row) {
            $this->assertSame($keys, array_keys($row));
        }
    }

    public function testFetchAssocLessKeys()
    {
        $keys = ['firstname'];
        $res = $this->csv->fetchAssoc($keys);
        $this->assertSame([['firstname' => 'john'], ['firstname' => 'jane']], $res);
    }

    public function testFetchAssocMoreKeys()
    {
        $keys = ['firstname', 'lastname', 'email', 'age'];
        $res = $this->csv->fetchAssoc($keys);

        foreach ($res as $row) {
            $this->assertCount(4, array_values($row));
            $this->assertNull($row['age']);
        }
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testFetchAssocKeyFailure()
    {
        $this->csv->fetchAssoc([['firstname', 'lastname', 'email', 'age']]);
    }

    public function testFetchCol()
    {
        $this->assertSame(['john', 'jane'], $this->csv->fetchCol(0));
        $this->assertSame(['john', 'jane'], $this->csv->fetchColumn());
    }

    public function testFetchColEmptyCol()
    {
        $raw = [
            ['john', 'doe'],
            ['lara', 'croft', 'lara.croft@example.com']
        ];

        $file = new SplTempFileObject;
        foreach ($raw as $row) {
            $file->fputcsv($row);
        }
        $csv = new Reader($file);
        $res = $csv->fetchColumn(2);
        $this->assertInternalType('array', $res);
        $this->assertCount(2, $res);
        $this->assertNull($res[0][2]);
    }

    public function testFetchColCallback()
    {
        $func = function ($value) {
            return array_map('strtoupper', $value);
        };

        $this->assertSame(['JOHN', 'JANE'], $this->csv->fetchCol(0, $func));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFetchColFailure()
    {
        $this->csv->fetchCol('toto');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testfetchOne()
    {
        $this->assertSame($this->expected[0], $this->csv->fetchOne(0));
        $this->assertSame($this->expected[1], $this->csv->fetchOne(1));
        $this->assertSame([], $this->csv->fetchOne(35));
        $this->csv->fetchOne(-5);
    }

    public function testEach()
    {
        $transform = [];
        $res = $this->csv->each(function ($row) use (&$transform) {
            $transform[] = array_map('strtoupper', $row);

            return true;
        });
        $this->assertSame($res, 2);
        $this->assertSame(strtoupper($this->expected[0][0]), $transform[0][0]);
        $res = $this->csv->each(function ($row, $index) {
            if ($index > 0) {
                return false;
            }

            return true;
        });
        $this->assertSame($res, 1);
    }

    public function testGetWriter()
    {
        $writer = $this->csv->getWriter();
        $writer->insertOne(['toto', 'le', 'herisson']);
        $expected = <<<EOF
<table class="table-csv-data">
<tr>
<td>john</td>
<td>doe</td>
<td>john.doe@example.com</td>
</tr>
<tr>
<td>jane</td>
<td>doe</td>
<td>jane.doe@example.com</td>
</tr>
<tr>
<td>toto</td>
<td>le</td>
<td>herisson</td>
</tr>
</table>
EOF;
        $this->assertSame($expected, $writer->toHTML());
    }

    public function testGetWriter2()
    {
        $csv = (new Reader(__DIR__.'/foo.csv'))->getWriter('a+');
        $this->assertInstanceOf('\League\Csv\Writer', $csv);
    }
}
