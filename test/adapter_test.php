<?php
    // $Id$
    
    class TestOfPearAdapter extends PHPUnit_TestCase {
        function TestOfPearAdapter() {
            $this->PHPUnit_TestCase();
        }
        function testBoolean() {
            $this->assertTrue(true, "PEAR true");
            $this->assertFalse(false, "PEAR false");
        }
        function testPass() {
            $this->pass("PEAR pass");
        }
    }
?>