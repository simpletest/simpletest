<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'simple_unit.php');
    require_once(SIMPLE_TEST . 'simple_html_test.php');
    
    class TestOfUnitTestCase extends UnitTestCase {
        function TestOfUnitTestCase() {
            $this->UnitTestCase();
        }
        function testOfFalse() {
            $this->assertFalse(true, "True is not false");        // Fail.
            $this->assertFalse(false, "False is false");
        }
        function testOfNull() {
            $this->assertNull(null);
            $this->assertNull(false);        // Fail.
            $this->assertNotNull(null);        // Fail.
            $this->assertNotNull(false);
        }
        function testOfType() {
            $this->assertIsA("hello", "string");
            $this->assertIsA(14, "string");        // Fail.
            $this->assertIsA($this, "TestOfUnitTestCase");
            $this->assertIsA($this, "UnitTestCase");
            $this->assertIsA(14, "TestOfUnitTestCase");        // Fail.
            $this->assertIsA($this, "TestHTMLDisplay");        // Fail.
        }
        function testOfEquality() {
            $this->assertEqual("0", 0);
            $this->assertEqual(1, 2);        // Fail.
            $this->assertNotEqual("0", 0);        // Fail.
            $this->assertNotEqual(1, 2);
        }
        function testOfIdentity() {
            $a = "fred";
            $b = $a;
            $this->assertIdentical($a, $b);
            $this->assertNotIdentical($a, $b);       // Fail.
            $a = "0";
            $b = 0;
            $this->assertIdentical($a, $b);        // Fail.
            $this->assertNotIdentical($a, $b);
        }
        function testOfReference() {
            $a = "fred";
            $b = &$a;
            $this->assertReference($a, $b);
            $this->assertCopy($a, $b);        // Fail.
            $c = "Hello";
            $this->assertReference($a, $c);        // Fail.
            $this->assertCopy($a, $c);
        }
        function testOfPatterns() {
            $this->assertWantedPattern('/hello/i', "Hello there");
            $this->assertNoUnwantedPattern('/hello/', "Hello there");
            $this->assertWantedPattern('/hello/', "Hello there");            // Fail.
            $this->assertNoUnwantedPattern('/hello/i', "Hello there");      // Fail.
        }
    }
    
    $test = new GroupTest("Unit test case test, 14 fails and 14 passes");
    $display = new TestHTMLDisplay();
    $test->attachObserver($display);
    $test->addTestCase(new TestOfUnitTestCase());
    
    $test->run();
?>