<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'unit_tester.php');
    require_once(SIMPLE_TEST . 'web_tester.php');
    require_once(SIMPLE_TEST . 'simple_html_test.php');
    require_once(SIMPLE_TEST . 'simple_mock.php');
    require_once(SIMPLE_TEST . 'adapters/pear_test_case.php');
    require_once(SIMPLE_TEST . 'adapters/phpunit_test_case.php');
    
    ini_set("allow_call_time_pass_reference", "1");
   
    class UnitTests extends GroupTest {
        function UnitTests() {
            $this->GroupTest("Unit tests");
            $this->addTestFile("errors_test.php");
            $this->addTestFile("options_test.php");
            $this->addTestFile("assertion_test.php");
            $this->addTestFile("simple_mock_test.php");
            $this->addTestFile("adapter_test.php");
            $this->addTestFile("socket_test.php");
            $this->addTestFile("http_test.php");
            $this->addTestFile("browser_test.php");
            $this->addTestFile("parser_test.php");
            $this->addTestFile("tag_test.php");
            $this->addTestFile("page_test.php");
            $this->addTestFile("web_test_case_test.php");
        }
    }
    
    if (!defined("TEST_RUNNING")) {
        define("TEST_RUNNING", true);
        $test = &new UnitTests();
        $test->run(new TestHtmlDisplay());
    }
?>