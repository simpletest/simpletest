<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'simple_unit.php');
    require_once(SIMPLE_TEST . 'simple_html_test.php');
    require_once(SIMPLE_TEST . 'simple_mock.php');
    
    if (!defined("TEST_RUNNING")) {
        define("TEST_RUNNING", true);
        
        require_once('unit_tests.php');
        require_once('boundary_tests.php');
    }
        
    class AllTests extends GroupTest {
        function AllTests() {
            $this->GroupTest("All tests");
            $this->AddTestCase(new UnitTests());
            $this->AddTestCase(new BoundaryTests());
        }
    }

    $test = &new AllTests();
    $test->attachObserver(new TestHtmlDisplay());
    $test->run();
?>