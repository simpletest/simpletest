<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'simple_unit.php');
    require_once(SIMPLE_TEST . 'simple_html_test.php');
    require_once(SIMPLE_TEST . 'simple_mock.php');
    
    class BoundaryTests extends GroupTest {
        function BoundaryTests() {
            $this->GroupTest("Boundary tests");
            $this->addTestFile("live_test.php");
        }
    }
    
    if (!defined("TEST_RUNNING")) {
        define("TEST_RUNNING", true);
        $test = &new BoundaryTests("Boundary tests");
        $test->attachObserver(new TestHtmlDisplay());
        $test->run();
    }
?>