<?php

require_once __DIR__ . '/../../../src/autorun.php';

class CoverageUtilsTest extends UnitTestCase
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
        require_once __DIR__ . '/../coverage_utils.php';
    }

    public function testReportFilename()
    {
        $this->assertEqual('C__Oh_No_parula.php.html', CoverageUtils::reportFilename('C:\Oh\No\parula.php'));
        $this->assertEqual('parula.php.html', CoverageUtils::reportFilename('parula.php'));
        $this->assertEqual('warbler_parula.php.html', CoverageUtils::reportFilename('warbler/parula.php'));
        $this->assertEqual('warbler_parula.php.html', CoverageUtils::reportFilename('warbler\\parula.php'));
    }

    public function testMkdir()
    {
        CoverageUtils::mkdir(__DIR__);
        try {
            CoverageUtils::mkdir(__FILE__);
            $this->fail('Should give error about cannot create dir of a file');
        } catch (Exception $expected) {
        }
    }

    public function testIsPackageClassAvailable()
    {
        $coverageSource = __DIR__ . '/../coverage_calculator.php';
        $this->assertTrue(CoverageUtils::isPackageClassAvailable($coverageSource, 'CoverageCalculator'));
        $this->assertFalse(CoverageUtils::isPackageClassAvailable($coverageSource, 'BogusCoverage'));
        $this->assertFalse(CoverageUtils::isPackageClassAvailable('bogus-file', 'BogusCoverage'));
        $this->assertTrue(CoverageUtils::isPackageClassAvailable('bogus-file', 'CoverageUtils'));
    }

    public function testParseArgumentsMultiValue()
    {
        $actual   = CoverageUtils::parseArguments(['scriptname', '--a=b', '--a=c'], true);
        $expected = ['extraArguments' => [], 'a' => 'c', 'a[]' => ['b', 'c']];
        $this->assertEqual($expected, $actual);
    }

    public function testParseArguments()
    {
        $actual   = CoverageUtils::parseArguments(['scriptname', '--a=b', '-c', 'xxx']);
        $expected = ['a' => 'b', 'c' => '', 'extraArguments' => ['xxx']];
        $this->assertEqual($expected, $actual);
    }

    public function testParseDoubleDashNoArguments()
    {
        $actual = CoverageUtils::parseArguments(['scriptname', '--aa']);
        $this->assertTrue(isset($actual['aa']));
    }

    public function testParseHyphenedExtraArguments()
    {
        $actual   = CoverageUtils::parseArguments(['scriptname', '--alpha-beta=b', 'gamma-lambda']);
        $expected = ['alpha-beta' => 'b', 'extraArguments' => ['gamma-lambda']];
        $this->assertEqual($expected, $actual);
    }

    public function testAddItemAsArray()
    {
        $actual = [];
        CoverageUtils::addItemAsArray($actual, 'bird', 'duck');
        $this->assertEqual(['bird[]' => ['duck']], $actual);

        CoverageUtils::addItemAsArray($actual, 'bird', 'pigeon');
        $this->assertEqual(['bird[]' => ['duck', 'pigeon']], $actual);
    }

    public function testIssetOrDefault()
    {
        $data = ['bird' => 'gull'];
        $this->assertEqual('lab', CoverageUtils::issetOrDefault($data['dog'], 'lab'));
        $this->assertEqual('gull', CoverageUtils::issetOrDefault($data['bird'], 'sparrow'));
    }
}
