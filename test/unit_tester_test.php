<?php
    // $Id$
    
    class TestOfUnitTester extends UnitTestCase {
        
        function testAssertTrueReturnsAssertionAsBoolean() {
            $this->assertTrue($this->assertTrue(true));
        }
        
        function testAssertFalseReturnsAssertionAsBoolean() {
            $this->assertTrue($this->assertFalse(false));
        }
        
        function testAssertEqualReturnsAssertionAsBoolean() {
            $this->assertTrue($this->assertEqual(5, 5));
        }
        
        function testAssertIdenticalReturnsAssertionAsBoolean() {
            $this->assertTrue($this->assertIdentical(5, 5));
        }
    }
?>