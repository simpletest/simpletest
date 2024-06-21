<?php

require_once __DIR__ . '/../../../src/autorun.php';

class CoverageCalculatorTest extends UnitTestCase
{
    public function skip()
    {
        $this->skipIf(
            !extension_loaded('sqlite3'),
            'The Coverage extension requires the PHP extension "php_sqlite3".'
        );
    }

    protected function setUp()
    {
        require_once __DIR__ . '/../coverage_calculator.php';
        $this->calc = new CoverageCalculator();
    }

    public function testVariables()
    {
        $coverage  = ['file' => [1, 1, 1, 1]];
        $untouched = ['missed-file'];
        $variables = $this->calc->variables($coverage, $untouched);
        $this->assertEqual(4, $variables['totalLinesOfCode']);
        $this->assertEqual(4, $variables['totalLinesOfCoverage']);
        $this->assertEqual(100, $variables['totalPercentCoverage']);
        $expected = ['file' => ['fileReport' => 'file.html', 'percentage' => 100]];
        $this->assertEqual($expected, $variables['coverageByFile']);
        $this->assertEqual(50, $variables['filesTouchedPercentage']);
        $this->assertEqual($untouched, $variables['untouched']);
    }

    public function testPercentageCoverageForFile()
    {
        $coverage = [0,0,0,1,1,1];
        $result = $this->calc->percentCoverageForFile('file', $coverage);
        $this->assertEqual(50, $result['percentage']);
        $this->assertEqual('file.html', $result['fileReport']);
    }

    public function testTotalLinesOfCode()
    {
        $this->assertEqual(13, $this->calc->totalLinesOfCode(10, [1, 2, 3]));
    }

    public function testLineCoverage()
    {
        $this->assertEqual(10, $this->calc->lineCoverage(10, -1));
        $this->assertEqual(10, $this->calc->lineCoverage(10, 0));
        $this->assertEqual(11, $this->calc->lineCoverage(10, 1));
    }

    public function testTotalCoverage()
    {
        $this->assertEqual(11, $this->calc->totalCoverage(10, [-1, 1]));
    }
}
