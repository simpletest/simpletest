<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'simple_adapter.php');
    
    /**
     *    Adapter for sourceforge PHPUnit test case to allow
     *    legacy test cases to be used with SimpleTest.
     */
    class TestCase extends TestCaseAdapter {
        function PHPUnit_TestCase($name) {
        }
        function assertEquals($expected, $actual, $message = 0) {
        }
        function assertRegexp($regexp, $actual, $message = false) {
        }
        function assertEqualsMultilineStrings($string0, $string1, $message = "") {
        }                             
        function fail($message = 0) {
        }
        function error($message) {
        }
        function name() {
        }
    }
?>