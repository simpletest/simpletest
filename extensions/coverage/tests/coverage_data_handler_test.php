<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/autorun.php';

class CoverageDataHandlerTest extends UnitTestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../coverage_data_handler.php';
    }

    public function skip(): void
    {
        $this->skipIf(
            !\extension_loaded('sqlite3'),
            'The Coverage extension requires the PHP extension "php_sqlite3".',
        );
    }

    public function testAggregateCoverageCode(): void
    {
        $handler = new CoverageDataHandler($this->tempdb());
        $this->assertEqual(-2, $handler->aggregateCoverageCode(-2, -2));
        $this->assertEqual(-2, $handler->aggregateCoverageCode(-2, 10));
        $this->assertEqual(-2, $handler->aggregateCoverageCode(10, -2));
        $this->assertEqual(-1, $handler->aggregateCoverageCode(-1, -1));
        $this->assertEqual(10, $handler->aggregateCoverageCode(-1, 10));
        $this->assertEqual(10, $handler->aggregateCoverageCode(10, -1));
        $this->assertEqual(20, $handler->aggregateCoverageCode(10, 10));
    }

    public function testSimpleWriteRead(): void
    {
        $handler = new CoverageDataHandler($this->tempdb());
        $handler->createSchema();
        $coverage = [10 => -2, 20 => -1, 30 => 0, 40 => 1];
        $handler->write(['file' => $coverage]);

        $actual   = $handler->readFile('file');
        $expected = [10 => -2, 20 => -1, 30 => 0, 40 => 1];
        $this->assertEqual($expected, $actual);
    }

    public function testMultiFileWriteRead(): void
    {
        $handler = new CoverageDataHandler($this->tempdb());
        $handler->createSchema();
        $handler->write(['file1' => [-2, -1, 1], 'file2' => [-2, -1, 1]]);
        $handler->write(['file1' => [-2, -1, 1]]);

        $expected = ['file1' => [-2, -1, 2], 'file2' => [-2, -1, 1]];
        $actual   = $handler->read();
        $this->assertEqual($expected, $actual);
    }

    public function testGetfilenames(): void
    {
        $handler = new CoverageDataHandler($this->tempdb());
        $handler->createSchema();
        $rawCoverage = ['file0' => [], 'file1' => []];
        $handler->write($rawCoverage);
        $actual = $handler->getFilenames();
        $this->assertEqual(['file0', 'file1'], $actual);
    }

    public function testWriteUntouchedFiles(): void
    {
        $handler = new CoverageDataHandler($this->tempdb());
        $handler->createSchema();
        $handler->writeUntouchedFile('bluejay');
        $handler->writeUntouchedFile('robin');
        $this->assertEqual(['bluejay', 'robin'], $handler->readUntouchedFiles());
    }

    public function testLtrim(): void
    {
        $this->assertEqual('ber', CoverageDataHandler::ltrim('goo', 'goober'));
        $this->assertEqual('some/file', CoverageDataHandler::ltrim('./', './some/file'));
        $this->assertEqual('/x/y/z/a/b/c', CoverageDataHandler::ltrim('/a/b/', '/x/y/z/a/b/c'));
    }

    public function tempdb()
    {
        return \tempnam(null, 'coverage.test.db');
    }
}
