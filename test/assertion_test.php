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
        function testBoolean() {
            $is_true = &new EqualityAssertion(true);
            $this->assertTrue($is_true->test(true));
            $this->assertFalse($is_true->test(false));
            $this->assertEqual($is_true->testMessage(true), "equalityassertion [Boolean: true]");
            $this->assertEqual(
                    $is_true->testMessage(false),
                    "equalityassertion [Boolean: true] fails with [Boolean: false]");
        }
        function testStringMatch() {
            $hello = &new EqualityAssertion("Hello");
            $this->assertTrue($hello->test("Hello"));
            $this->assertFalse($hello->test("Goodbye"));
            $this->assertEqual($hello->testMessage("Hello"), "equalityassertion [String: Hello]");
            $this->assertEqual(
                    $hello->testMessage("Goodbye"),
                    "equalityassertion [String: Hello] fails with [String: Goodbye] at character 0");
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
                        "equalityassertion [String: abcdefg] fails with [String: $compare] at character $position");
            }
            $str = &new EqualityAssertion("abcdefghi");
            foreach ($comparisons as $compare => $position) {
                $this->assertEqual(
                        $str->testMessage($compare),
                        "equalityassertion [String: abcdefghi] fails with [String: $compare] at character $position");
            }
        }
        function testInteger() {
            $fifteen = &new EqualityAssertion(15);
            $this->assertTrue($fifteen->test(15));
            $this->assertFalse($fifteen->test(14));
            $this->assertEqual($fifteen->testMessage(15), "equalityassertion [Integer: 15]");
            $this->assertEqual(
                    $fifteen->testMessage(14),
                    "equalityassertion [Integer: 15] fails with [Integer: 14] by 1");
        }
        function testFloat() {
            $pi = &new EqualityAssertion(3.14);
            $this->assertTrue($pi->test(3.14));
            $this->assertFalse($pi->test(3.15));
            $this->assertEqual($pi->testMessage(3.14), "equalityassertion [Float: 3.14]");
            $this->assertEqual(
                    $pi->testMessage(3.15),
                    "equalityassertion [Float: 3.14] fails with [Float: 3.15]");
        }
        function testArray() {
            $colours = &new EqualityAssertion(array("r", "g", "b"));
            $this->assertTrue($colours->test(array("r", "g", "b")));
            $this->assertFalse($colours->test(array("g", "b", "r")));
            $this->assertEqual(
                    $colours->testMessage(array("r", "g", "b")),
                    "equalityassertion [Array: 3 items]");
            $this->assertEqual(
                    $colours->testMessage(array("r", "g", "z")),
                    "equalityassertion [Array: 3 items] fails with [Array: 3 items] key 2 at character 0");
            $this->assertEqual(
                    $colours->testMessage(array("r", "g")),
                    "equalityassertion [Array: 3 items] fails with [Array: 2 items] key 2 does not exist in second array");
            $this->assertEqual(
                    $colours->testMessage(array("r", "g", "b", "z")),
                    "equalityassertion [Array: 3 items] fails with [Array: 4 items] key 3 does not exist in first array");
        }
        function testHash() {
            $blue = &new EqualityAssertion(array("r" => 0, "g" => 0, "b" => 255));
            $this->assertTrue($blue->test(array("r" => 0, "g" => 0, "b" => 255)));
            $this->assertFalse($blue->test(array("r" => 0, "g" => 255, "b" => 0)));
            $this->assertEqual(
                    $blue->testMessage(array("r" => 0, "g" => 0, "b" => 255)),
                    "equalityassertion [Array: 3 items]");
            $this->assertEqual(
                    $blue->testMessage(array("r" => 0, "g" => 0, "b" => 254)),
                    "equalityassertion [Array: 3 items] fails with [Array: 3 items] key b by 1");
        }
        function testNestedHash() {
            $tree = &new EqualityAssertion(array(
                    "a" => 1,
                    "b" => array(
                            "c" => 2,
                            "d" => "Three")));
            $this->assertEqual(
                    $tree->testMessage(array(
                        "a" => 1,
                        "b" => array(
                                "c" => 2,
                                "d" => "Threeish"))),
                    "equalityassertion [Array: 2 items] fails with [Array: 2 items] key b key d at character 5");
        }
    }
?>