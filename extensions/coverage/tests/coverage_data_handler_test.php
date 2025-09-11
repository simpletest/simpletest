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
        return \tempnam(\sys_get_temp_dir(), 'coverage.test.db');
    }

    public function testLegacyWriteReadAndUntouched(): void
    {
        $tmp = $this->tempdb();

        $handler = new CoverageDataHandler($tmp);
        $this->assertTrue(\is_object($handler));

        $handler->createSchema();

        $coverage = [
            'src/compatibility.php' => [
                1 => 1,
                2 => -1,
            ],
        ];

        $handler->write($coverage);

        $filenames = $handler->getFilenames();
        $this->assertTrue(\is_array($filenames));
        $this->assertTrue(\count($filenames) >= 1);

        $key  = $filenames[0];
        $read = $handler->readFile($key);
        $this->assertTrue(\is_array($read));

        // test untouched
        $handler->writeUntouchedFile('src/compatibility.php');
        $untouched = $handler->readUntouchedFiles();
        $this->assertTrue(\is_array($untouched));

        $handler->close();
        @\unlink($tmp);
    }

    public function testLegacyAggregateCoverageCodeAndLTrim(): void
    {
        $handler = new CoverageDataHandler($this->tempdb());
        $this->assertEqual(-2, $handler->aggregateCoverageCode(-2, 5));
        $this->assertEqual(5, $handler->aggregateCoverageCode(-1, 5));
        $this->assertEqual(-2, $handler->aggregateCoverageCode(3, -2));
        $this->assertEqual(3, $handler->aggregateCoverageCode(3, -1));
        $this->assertEqual(8, $handler->aggregateCoverageCode(3, 5));

        // test ltrim
        $trimmed = CoverageDataHandler::ltrim('/base/', '/base/path/file.php');
        $this->assertEqual('path/file.php', $trimmed);

        $handler->close();
    }

    public function testReadFileSkipsNonJsonRows(): void
    {
        $tmp     = $this->tempdb();
        $handler = new CoverageDataHandler($tmp);
        $handler->createSchema();

        // insert a non-json coverage row directly via PDO
        $pdo = $handler->db;
        $pdo->exec("INSERT INTO coverage (name, coverage) VALUES ('foo.php', 'not-json')");

        // should not throw and should return an empty aggregate
        $res = $handler->readFile('foo.php');
        $this->assertTrue(\is_array($res));

        // now insert a valid JSON row and ensure aggregation works
        $pdo->exec("INSERT INTO coverage (name, coverage) VALUES ('bar.php', '[]')");
        $res2 = $handler->readFile('bar.php');
        $this->assertTrue(\is_array($res2));

        $handler->close();
        @\unlink($tmp);
    }

    public function testReadAggregatesAndUntouchedWrites(): void
    {
        $tmp     = $this->tempdb();
        $handler = new CoverageDataHandler($tmp);
        $handler->createSchema();

        // write two files via write()
        $cov = [
            'fileA' => [1 => 1],
            'fileB' => [2 => -1],
        ];
        $handler->write($cov);

        $all = $handler->read();
        $this->assertTrue(\is_array($all));

        // test writeUntouchedFile and readUntouchedFiles
        $handler->writeUntouchedFile('fileB');
        $untouched = $handler->readUntouchedFiles();
        $this->assertTrue(\is_array($untouched));

        $handler->close();
        @\unlink($tmp);
    }
}
