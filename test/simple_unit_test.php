<?php
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
        function testOfEquality() {
            $this->assertEqual("0", 0);
            $this->assertEqual(1, 2);        // Fail.
        }
        function testOfIdentity() {
            $a = "fred";
            $b = $a;
            $this->assertIdentical($a, $b);
            $a = "0";
            $b = 0;
            $this->assertIdentical($a, $b);        // Fail.
        }
        function testOfReference() {
            $a = "fred";
            $b = &$a;
            $this->assertReference($a, $b);
            $c = "Hello";
            $this->assertReference($a, $c);        // Fail.
        }
        function testOfPatterns() {
            $this->assertWantedPattern("Hello there", '/hello/i');
            $this->assertNoUnwantedPattern("Hello there", '/hello/');
            $this->assertWantedPattern("Hello there", '/hello/');            // Fail.
            $this->assertNoUnwantedPattern("Hello there", '/hello/i');      // Fail.
        }
    }
    
    $test = new SimpleTest("Unit test case test, 8 fails and 8 passes");
    $display = new TestHTMLDisplay();
    $test->attachObserver($display);
    $test->addTestCase(new TestOfUnitTestCase());
    
    $test->run();
?>