<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    define("TEST_RUNNING", true);
    
    require_once(SIMPLE_TEST . 'unit_tester.php');
    require_once(SIMPLE_TEST . 'shell_tester.php');
    require_once(SIMPLE_TEST . 'reporter.php');
    require_once(SIMPLE_TEST . 'mock_objects.php');
    require_once('unit_tests.php');
    require_once('boundary_tests.php');
        
    class AllTests extends GroupTest {
        function AllTests() {
            $this->GroupTest("All tests for SimpleTest " . SimpleTestOptions::getVersion());
            $this->AddTestCase(new UnitTests());
            $this->AddTestCase(new BoundaryTests());
        }
    }

    $test = &new AllTests();
    if (SimpleReporter::inCli()) {
        exit ($test->run(new TextReporter()) ? 0 : 1);
    }
    $test->run(new HtmlReporter());
?>