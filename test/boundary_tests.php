<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'unit_tester.php');
    require_once(SIMPLE_TEST . 'web_tester.php');
    require_once(SIMPLE_TEST . 'reporter.php');
    require_once(SIMPLE_TEST . 'mock_objects.php');
    
    class BoundaryTests extends GroupTest {
        function BoundaryTests() {
            $this->GroupTest("Boundary tests");
            $this->addTestFile("live_test.php");
            $this->addTestFile("real_sites_test.php");
        }
    }
    
    if (!defined("TEST_RUNNING")) {
        define("TEST_RUNNING", true);
        $test = &new BoundaryTests("Boundary tests");
        if (CommandLineReporter::inCli()) {
            exit ($test->run(new CommandLineReporter()) ? 0 : 1);
        }
        $test->run(new TestHtmlDisplay());
    }
?>