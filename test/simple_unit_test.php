<?php
    // $Id$
    
    // NOTE..
    // Some of these tests are designed to fail! Do not be alarmed.
    //                         ----------------
    
    // The following tests are a bit hacky. Whilst Kent Beck tried to
    // build a unit tester with a unit tester I am not that brave.
    // Instead I have just hacked together odd test scripts until
    // I have enough of a tester to procede more formally.
    //
    // The proper tests start in all_tests.php
    
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
            $this->assertNull(null, "#%s#");
            $this->assertNull(false, "#%s#");        // Fail.
            $this->assertNotNull(null, "#%s#");        // Fail.
            $this->assertNotNull(false, "#%s#");
        }
        function testOfType() {
            $this->assertIsA("hello", "string", "#%s#");
            $this->assertIsA(14, "string", "#%s#");        // Fail.
            $this->assertIsA($this, "TestOfUnitTestCase", "#%s#");
            $this->assertIsA($this, "UnitTestCase", "#%s#");
            $this->assertIsA(14, "TestOfUnitTestCase", "#%s#");        // Fail.
            $this->assertIsA($this, "TestHTMLDisplay", "#%s#");        // Fail.
        }
        function testOfEquality() {
            $this->assertEqual("0", 0, "#%s#");
            $this->assertEqual(1, 2, "#%s#");        // Fail.
            $this->assertNotEqual("0", 0, "#%s#");        // Fail.
            $this->assertNotEqual(1, 2, "#%s#");
        }
        function testOfIdentity() {
            $a = "fred";
            $b = $a;
            $this->assertIdentical($a, $b, "#%s#");
            $this->assertNotIdentical($a, $b, "#%s#");       // Fail.
            $a = "0";
            $b = 0;
            $this->assertIdentical($a, $b, "#%s#");        // Fail.
            $this->assertNotIdentical($a, $b, "#%s#");
        }
        function testOfReference() {
            $a = "fred";
            $b = &$a;
            $this->assertReference($a, $b, "#%s#");
            $this->assertCopy($a, $b, "#%s#");        // Fail.
            $c = "Hello";
            $this->assertReference($a, $c, "#%s#");        // Fail.
            $this->assertCopy($a, $c, "#%s#");
        }
        function testOfPatterns() {
            $this->assertWantedPattern('/hello/i', "Hello there", "#%s#");
            $this->assertNoUnwantedPattern('/hello/', "Hello there", "#%s#");
            $this->assertWantedPattern('/hello/', "Hello there", "#%s#");            // Fail.
            $this->assertNoUnwantedPattern('/hello/i', "Hello there", "#%s#");      // Fail.
        }
    }
    
    $test = new GroupTest("Unit test case test, 14 fails and 14 passes");
    $display = new TestHTMLDisplay();
    $test->attachObserver($display);
    $test->addTestCase(new TestOfUnitTestCase());
    
    $test->run();
?>