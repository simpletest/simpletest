<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'simple_unit.php');
    require_once(SIMPLE_TEST . 'simple_html_test.php');
    require_once(SIMPLE_TEST . 'simple_mock.php');
    
    $test = new GroupTest("All tests");
    $test->addTestFile("simple_mock_test.php");
    $test->addTestFile("web_test_test.php");
    $test->addTestFile("socket_test.php");
    $test->addTestFile("http_test.php");
    $test->addTestFile("browser_test.php");
    $test->attachObserver(new TestHtmlDisplay());
    $test->run();
?>