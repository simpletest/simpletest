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
            $is_true = &new EqualAssertion(true);
            $this->assertTrue($is_true->test(true));
            $this->assertFalse($is_true->test(false));
            $this->assertEqual($is_true->testMessage(true), "Equal assertion [Boolean: true]");
            $this->assertEqual(
                    $is_true->testMessage(false),
                    "Equal assertion [Boolean: true] fails with [Boolean: false]");
        }
        function testStringMatch() {
            $hello = &new EqualAssertion("Hello");
            $this->assertTrue($hello->test("Hello"));
            $this->assertFalse($hello->test("Goodbye"));
            $this->assertEqual($hello->testMessage("Hello"), "Equal assertion [String: Hello]");
            $this->assertEqual(
                    $hello->testMessage("Goodbye"),
                    "Equal assertion [String: Hello] fails with [String: Goodbye] at character 0");
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
            $str = &new EqualAssertion("abcdefg");
            foreach ($comparisons as $compare => $position) {
                $this->assertEqual(
                        $str->testMessage($compare),
                        "Equal assertion [String: abcdefg] fails with [String: $compare] at character $position");
            }
            $str = &new EqualAssertion("abcdefghi");
            foreach ($comparisons as $compare => $position) {
                $this->assertEqual(
                        $str->testMessage($compare),
                        "Equal assertion [String: abcdefghi] fails with [String: $compare] at character $position");
            }
        }
        function testInteger() {
            $fifteen = &new EqualAssertion(15);
            $this->assertTrue($fifteen->test(15));
            $this->assertFalse($fifteen->test(14));
            $this->assertEqual($fifteen->testMessage(15), "Equal assertion [Integer: 15]");
            $this->assertEqual(
                    $fifteen->testMessage(14),
                    "Equal assertion [Integer: 15] fails with [Integer: 14] by 1");
        }
        function testFloat() {
            $pi = &new EqualAssertion(3.14);
            $this->assertTrue($pi->test(3.14));
            $this->assertFalse($pi->test(3.15));
            $this->assertEqual($pi->testMessage(3.14), "Equal assertion [Float: 3.14]");
            $this->assertEqual(
                    $pi->testMessage(3.15),
                    "Equal assertion [Float: 3.14] fails with [Float: 3.15]");
        }
        function testArray() {
            $colours = &new EqualAssertion(array("r", "g", "b"));
            $this->assertTrue($colours->test(array("r", "g", "b")));
            $this->assertFalse($colours->test(array("g", "b", "r")));
            $this->assertEqual(
                    $colours->testMessage(array("r", "g", "b")),
                    "Equal assertion [Array: 3 items]");
            $this->assertEqual(
                    $colours->testMessage(array("r", "g", "z")),
                    "Equal assertion [Array: 3 items] fails with [Array: 3 items] key 2 at character 0");
            $this->assertEqual(
                    $colours->testMessage(array("r", "g")),
                    "Equal assertion [Array: 3 items] fails with [Array: 2 items] key 2 does not exist in second array");
            $this->assertEqual(
                    $colours->testMessage(array("r", "g", "b", "z")),
                    "Equal assertion [Array: 3 items] fails with [Array: 4 items] key 3 does not exist in first array");
        }
        function testHash() {
            $blue = &new EqualAssertion(array("r" => 0, "g" => 0, "b" => 255));
            $this->assertTrue($blue->test(array("r" => 0, "g" => 0, "b" => 255)));
            $this->assertFalse($blue->test(array("r" => 0, "g" => 255, "b" => 0)));
            $this->assertEqual(
                    $blue->testMessage(array("r" => 0, "g" => 0, "b" => 255)),
                    "Equal assertion [Array: 3 items]");
            $this->assertEqual(
                    $blue->testMessage(array("r" => 0, "g" => 0, "b" => 254)),
                    "Equal assertion [Array: 3 items] fails with [Array: 3 items] key b by 1");
        }
        function testNestedHash() {
            $tree = &new EqualAssertion(array(
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
                    "Equal assertion [Array: 2 items] fails with [Array: 2 items] key b key d at character 5");
        }
    }
    
    class TestOfInequality extends UnitTestCase {
        function TestOfInequality() {
            $this->UnitTestCase();
        }
        function testStringMismatch() {
            $not_hello = &new NotEqualAssertion("Hello");
            $this->assertTrue($not_hello->test("Goodbye"));
            $this->assertFalse($not_hello->test("Hello"));
            $this->assertEqual(
                    $not_hello->testMessage("Goodbye"),
                    "Not equal assertion differs at character 0");
            $this->assertEqual(
                    $not_hello->testMessage("Hello"),
                    "Not equal assertion [String: Hello] matches");
        }
    }
    
    class TestOfIdentity extends UnitTestCase {
        function TestOfIdentity() {
            $this->UnitTestCase();
        }
        function testType() {
            $string = &new IdenticalAssertion("37");
            $this->assertTrue($string->test("37"));
            $this->assertFalse($string->test(37));
            $this->assertFalse($string->test("38"));
            $this->assertEqual(
                    $string->testMessage("37"),
                    "Identical assertion [String: 37]");
            $this->assertEqual(
                    $string->testMessage(37),
                    "Identical assertion [String: 37] fails with [Integer: 37] by type");
            $this->assertEqual(
                    $string->testMessage("38"),
                    "Identical assertion [String: 37] fails with [String: 38] at character 1");
        }
    }
?>