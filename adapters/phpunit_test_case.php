<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'simple_test.php');
    
    /**
     *    Adapter for sourceforge PHPUnit test case to allow
     *    legacy test cases to be used with SimpleTest.
     */
    class TestCase extends SimpleTestCase {
        function TestCase($label) {
            $this->SimpleTestCase($label);
        }
        function assertEquals($expected, $actual, $message = false) {
        }
        function assertRegexp($regexp, $actual, $message = false) {
        }
        function assertEqualsMultilineStrings($string0, $string1, $message = "") {
        }                             
        function fail($message = false) {
        }
        function error($message) {
        }
        function name() {
        }
    }
?>