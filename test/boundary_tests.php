<?php
    // $Id$
    
    if (! defined('TEST')) {
        define('TEST', 'boundary');
    }
    require_once('../unit_tester.php');
    require_once('../shell_tester.php');
    require_once('../web_tester.php');
    require_once('../reporter.php');
    require_once('../mock_objects.php');
    
    class BoundaryTests extends GroupTest {
        function BoundaryTests() {
            $this->GroupTest('Boundary tests');
            $this->addTestFile('shell_test.php');
            $this->addTestFile('live_test.php');
            $this->addTestFile('real_sites_test.php');
        }
    }
    
    if (TEST == 'boundary') {
        $test = &new BoundaryTests('Boundary tests');
        if (SimpleReporter::inCli()) {
            exit ($test->run(new TextReporter()) ? 0 : 1);
        }
        $test->run(new HtmlReporter());
    }
?>