<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'simple_adapter.php');
    
    /**
     *    Adapter for PEAR PHPUnit test case to allow
     *    legacy PEAR test cases to be used with SimpleTest.
     */
    class PHPUnit_TestCase extends TestCaseAdapter {
        function PHPUnit_TestCase() {
        }
        function assertEquals($first, $second, $message = "", $delta = 0) {
        }
        function assertNotNull($object, $message = "") {
        }
        function assertNull($object, $message = "") {
        }
        function assertSame($first, $second, $message = "") {
        }
        function assertNotSame($first, $second, $message = "") {
        }
        function assertTrue($condition, $message = "") {
        }
        function assertFalse($condition, $message = "") {
        }
        function assertRegExp($pattern, $actual, $message = "") {
        }
        function assertType($type, $value, $message = "") {
        }
        function fail($message) {
        }
        function pass($message) {
        }
        function countTestCases() {
        }
        function getName() {
        }
        function setName($name) {
        }
    }
?>