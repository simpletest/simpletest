<?php

require_once dirname(__FILE__) . '/../../../autorun.php';

class CoverageReporterTest extends UnitTestCase
{
    public function skip()
    {
        $this->skipIf(
            !extension_loaded('sqlite3'),
            'The Coverage extension requires the PHP extension "php_sqlite3".'
        );
    }

    public function setUp()
    {
        require_once dirname(__FILE__) . '/../coverage_reporter.php';
        new CoverageReporter();
    }

    public function testreportFilename()
    {
        $this->assertEqual('parula.php.html', CoverageReporter::reportFilename('parula.php'));
        $this->assertEqual('warbler_parula.php.html', CoverageReporter::reportFilename('warbler/parula.php'));
        $this->assertEqual('warbler_parula.php.html', CoverageReporter::reportFilename('warbler\\parula.php'));
    }
}
