<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'simple_unit.php');
    
    /**
     *    Adapter for PEAR PHPUnit test case to allow
     *    legacy PEAR test cases to be used with SimpleTest.
     */
    class PHPUnit_TestCase extends TestCase {
        
        /**
         *    Constructor. Sets the test name.
         *    @param $label        Test name to display.
         *    @public
         */
        function PHPUnit_TestCase($label = false) {
            $this->TestCase($label);
        }
        
        /**
         *
         *    @param $message        Message to display.
         *    @public
         */
        function assertEquals($first, $second, $message = "", $delta = 0) {
        }
        
        /**
         *
         *    @param $message        Message to display.
         *    @public
         */
        function assertNotNull($value, $message = "") {
            parent::assertTrue(isset($value), $message);
        }
        
        /**
         *
         *    @param $message        Message to display.
         *    @public
         */
        function assertNull($value, $message = "") {
            parent::assertTrue(!isset($value), $message);
        }
        
        /**
         *
         *    @param $message        Message to display.
         *    @public
         */
        function assertSame($first, $second, $message = "") {
        }
        
        /**
         *
         *    @param $message        Message to display.
         *    @public
         */
        function assertNotSame($first, $second, $message = "") {
        }
        
        /**
         *
         *    @param $message        Message to display.
         *    @public
         */
        function assertTrue($condition, $message = "") {
            parent::assertTrue($condition, $message);
        }
        
        /**
         *
         *    @param $message        Message to display.
         *    @public
         */
        function assertFalse($condition, $message = "") {
            parent::assertTrue(!$condition, $message);
        }
        
        /**
         *
         *    @param $message        Message to display.
         *    @public
         */
        function assertRegExp($pattern, $actual, $message = "") {
        }
        
        /**
         *
         *    @param $message        Message to display.
         *    @public
         */
        function assertType($type, $value, $message = "") {
        }
        
        /**
         *    Sends a fail event.
         *    @param $message        Message to display.
         *    @public
         */
        function fail($message) {
            parent::assertTrue(false, $message);
        }
        
        /**
         *    Sends a pass event.
         *    @param $message        Message to display.
         *    @public
         */
        function pass($message) {
            parent::assertTrue(true, $message);
        }
        
        /**
         *
         */
        function countTestCases() {
            return $this->getSize();
        }
        
        /**
         *
         */
        function getName() {
        }
        
        /**
         *
         */
        function setName($name) {
        }
    }
?>