<?php

namespace League\Csv\Test\Exporter;

use League\Csv\Writer;
use League\Csv\Exporter\ColumnConsistencyValidator;
use PHPUnit_Framework_TestCase;
use SplFileObject;
use SplTempFileObject;

/**
 * @group validators
 */
class ColumnConsistencyValidatorTest extends PHPUnit_Framework_TestCase
{
    private $csv;

    public function setUp()
    {
        $this->csv = Writer::createFromFileObject(new SplTempFileObject());
    }

    public function tearDown()
    {
        $csv = new SplFileObject(dirname(__DIR__).'/foo.csv', 'w');
        $csv->setCsvControl();
        $csv->fputcsv(["john", "doe", "john.doe@example.com"], ",", '"');
        $this->csv = null;
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testColumsCountSetterGetter()
    {
        $consistency = new ColumnConsistencyValidator();
        $this->assertSame(-1, $consistency->getColumnsCount());
        $consistency->setColumnsCount(3);
        $this->assertSame(3, $consistency->getColumnsCount());
        $consistency->setColumnsCount('toto');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testColumsCountConsistency()
    {
        $consistency = new ColumnConsistencyValidator();
        $this->csv->addValidator($consistency, 'consistency');
        $this->csv->insertOne(['john', 'doe', 'john.doe@example.com']);
        $consistency->setColumnsCount(2);
        $this->csv->insertOne(['jane', 'jane.doe@example.com']);
        $consistency->setColumnsCount(3);
        $this->csv->insertOne(['jane', 'jane.doe@example.com']);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAutoDetectColumnsCount()
    {
        $consistency = new ColumnConsistencyValidator();
        $this->csv->addValidator($consistency, 'consistency');
        $consistency->autodetectColumnsCount();
        $this->assertSame(-1, $consistency->getColumnsCount());
        $this->csv->insertOne(['john', 'doe', 'john.doe@example.com']);
        $this->assertSame(3, $consistency->getColumnsCount());
        $this->csv->insertOne(['jane', 'jane.doe@example.com']);
    }
}
