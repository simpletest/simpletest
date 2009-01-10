<?php
require_once(dirname(__FILE__) . '/../../../autorun.php');
require_once dirname(__FILE__) .'/../coverage_reporter.php';

class CoverageReporterTest extends UnitTestCase {

    function testInitialization() {
        new CoverageReporter();
    }

    function testreportFilename() {
        $this->assertEqual("parula.php.html", CoverageReporter::reportFilename("parula.php"));
        $this->assertEqual("warbler_parula.php.html", CoverageReporter::reportFilename("warbler/parula.php"));
        $this->assertEqual("warbler_parula.php.html", CoverageReporter::reportFilename("warbler\\parula.php"));
    }
}
?>