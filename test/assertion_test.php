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
        function testStringPosition() {
            $comparisons = array(
                    "abcdef" => 6,
                    "abcde" => 5,
                    "abcd" => 4,
                    "abc" => 3,
                    "ab" => 2,
                    "a" => 1,
                    "abcdefgz" => 7,
                    "abcdefz" => 6,
                    "abcdez" => 5,
                    "abcdz" => 4,
                    "abcz" => 3,
                    "abz" => 2,
                    "az" => 1,
                    "z" => 0);
            $str = &new EqualityAssertion("abcdefg");
            foreach ($comparisons as $compare => $position) {
                $this->assertEqual(
                        $str->testMessage($compare),
                        "[String: abcdefg] differs from [String: $compare] at character $position");
            }
            $str = &new EqualityAssertion("abcdefghi");
            foreach ($comparisons as $compare => $position) {
                $this->assertEqual(
                        $str->testMessage($compare),
                        "[String: abcdefghi] differs from [String: $compare] at character $position");
            }
        }
    }
?>