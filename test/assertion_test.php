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
                    "ab" => 2,
                    "a" => 1,
                    "abcz" => 3,
                    "abz" => 2,
                    "az" => 1,
                    "z" => 0);
            $str = &new EqualAssertion("abc");
            foreach ($comparisons as $compare => $position) {
                $this->assertEqual(
                        $str->testMessage($compare),
                        "Equal assertion [String: abc] fails with [String: $compare] at character $position");
            }
            $str = &new EqualAssertion("abcd");
            foreach ($comparisons as $compare => $position) {
                $this->assertEqual(
                        $str->testMessage($compare),
                        "Equal assertion [String: abcd] fails with [String: $compare] at character $position");
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
    
    class TestOfNonIdentity extends UnitTestCase {
        function TestOfNonIdentity() {
            $this->UnitTestCase();
        }
        function testType() {
            $string = &new NotIdenticalAssertion("37");
            $this->assertTrue($string->test("38"));
            $this->assertTrue($string->test(37));
            $this->assertFalse($string->test("37"));
            $this->assertEqual(
                    $string->testMessage("38"),
                    "Not identical assertion differs at character 1");
            $this->assertEqual(
                    $string->testMessage(37),
                    "Not identical assertion differs by type");
            $this->assertEqual(
                    $string->testMessage("37"),
                    "Not identical assertion [String: 37] matches");
        }
    }
    
    class TestOfPatterns extends UnitTestCase {
        function TestOfPatterns() {
            $this->UnitTestCase();
        }
        function testWanted() {
            $pattern = &new WantedPatternAssertion('/hello/i');
            $this->assertTrue($pattern->test("Hello world"));
            $this->assertEqual(
                    $pattern->testMessage("Hello world"),
                    "Pattern [/hello/i] detected in string [Hello world]");
            $this->assertEqual(
                    $pattern->testMessage("Goodbye world"),
                    "Pattern [/hello/i] not detected in string [Goodbye world]");
        }
        function testUnwanted() {
            $pattern = &new UnwantedPatternAssertion('/hello/i');
            $this->assertFalse($pattern->test("Hello world"));
            $this->assertEqual(
                    $pattern->testMessage("Hello world"),
                    "Pattern [/hello/i] detected in string [Hello world]");
            $this->assertEqual(
                    $pattern->testMessage("Goodbye world"),
                    "Pattern [/hello/i] not detected in string [Goodbye world]");
        }
    }
?>