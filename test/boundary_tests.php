<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'simple_unit.php');
    require_once(SIMPLE_TEST . 'simple_html_test.php');
    require_once(SIMPLE_TEST . 'simple_mock.php');
    
    $test = new GroupTest("Boundary tests");
    $test->addTestFile("live_test.php");
    $test->attachObserver(new TestHtmlDisplay());
    $test->run();
?>