<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'assertion.php');

    class TestOfAssertion extends UnitTestCase {
        function TestOfAssertion() {
            $this->UnitTestCase();
        }
        function testStringMatch() {
            $assertion = &new EqualityAssertion("Hello");
            $this->assertTrue($assertion->is("Hello"));
            $this->assertFalse($assertion->is("Goodbye"));
        }
    }
?>