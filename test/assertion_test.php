<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'assertion.php');

    class TestOfEquality extends UnitTestCase {
        function TestOfEquality() {
            $this->UnitTestCase();
        }
        function testStringMatch() {
            $hello = &new EqualityAssertion("Hello");
            $this->assertTrue($hello->test("Hello"));
            $this->assertFalse($hello->test("Goodbye"));
            $this->assertEqual($hello->testMessage("Hello"), "Equal [String: Hello]");
            $this->assertEqual(
                    $hello->testMessage("Goodbye"),
                    "[String: Hello] differs from [String: Goodbye] at character 0");
        }
    }
?>