<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'assertion.php');

    class TestOfAssertionFormatting extends UnitTestCase {
        function TestOfAssertionFormatting() {
            $this->UnitTestCase();
        }
        function testClipping() {
            $this->assertEqual(
                    Assertion::clipString("Hello", 6),
                    "Hello",
                    "Hello, 6->%s");
            $this->assertEqual(
                    Assertion::clipString("Hello", 5),
                    "Hello",
                    "Hello, 5->%s");
            $this->assertEqual(
                    Assertion::clipString("Hello world", 3),
                    "Hel...",
                    "Hello world, 3->%s");
            $this->assertEqual(
                    Assertion::clipString("Hello world", 6, 3),
                    "Hello ...",
                    "Hello world, 6, 3->%s");
            $this->assertEqual(
                    Assertion::clipString("Hello world", 3, 6),
                    "...o w...",
                    "Hello world, 3, 6->%s");
            $this->assertEqual(
                    Assertion::clipString("Hello world", 4, 11),
                    "...orld",
                    "Hello world, 4, 11->%s");
            $this->assertEqual(
                    Assertion::clipString("Hello world", 4, 12),
                    "...orld",
                    "Hello world, 4, 12->%s");
        }
    }

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
                    "Equal assertion [Boolean: true] fails with [Boolean: false] by value");
        }
        function testStringMatch() {
            $hello = &new EqualAssertion("Hello");
            $this->assertTrue($hello->test("Hello"));
            $this->assertFalse($hello->test("Goodbye"));
            $this->assertWantedPattern('/Equal assertion.*?Hello/', $hello->testMessage("Hello"));
            $this->assertWantedPattern('/fails/', $hello->testMessage("Goodbye"));
            $this->assertWantedPattern('/String: Hello/', $hello->testMessage("Goodbye"));
            $this->assertWantedPattern('/String: Goodbye/', $hello->testMessage("Goodbye"));
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
                $this->assertWantedPattern(
                        "/at character $position/",
                        $str->testMessage($compare));
            }
            $str = &new EqualAssertion("abcd");
            foreach ($comparisons as $compare => $position) {
                $this->assertWantedPattern(
                        "/at character $position/",
                        $str->testMessage($compare));
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
                    "Equal assertion [Float: 3.14] fails with [Float: 3.15] by value");
        }
        function testArray() {
            $colours = &new EqualAssertion(array("r", "g", "b"));
            $this->assertTrue($colours->test(array("r", "g", "b")));
            $this->assertFalse($colours->test(array("g", "b", "r")));
            $this->assertEqual(
                    $colours->testMessage(array("r", "g", "b")),
                    "Equal assertion [Array: 3 items]");
            $this->assertWantedPattern('/fails/', $colours->testMessage(array("r", "g", "z")));
            $this->assertWantedPattern(
                    '/key \[2\] at character 0/',
                    $colours->testMessage(array("r", "g", "z")));
            $this->assertWantedPattern(
                    '/keys .*? do not match/',
                    $colours->testMessage(array("r", "g")));
            $this->assertWantedPattern(
                    '/keys .*? do not match/',
                    $colours->testMessage(array("r", "g", "b", "z")));
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
                    "Equal assertion [Array: 3 items] fails with [Array: 3 items] key [b] by 1");
        }
        function testNestedHash() {
            $tree = &new EqualAssertion(array(
                    "a" => 1,
                    "b" => array(
                            "c" => 2,
                            "d" => "Three")));
            $this->assertWantedPattern(
                    '/key \[b\] key \[d\] at character 5/',
                    $tree->testMessage(array(
                        "a" => 1,
                        "b" => array(
                                "c" => 2,
                                "d" => "Threeish"))));
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
            $this->assertWantedPattern(
                    '/differs at character 0/',
                    $not_hello->testMessage("Goodbye"));
            $this->assertWantedPattern(
                    '/matches/',
                    $not_hello->testMessage("Hello"));
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
            $this->assertWantedPattern(
                    '/fails with \[Integer: 37\] by type/',
                    $string->testMessage(37));
            $this->assertWantedPattern(
                    '/at character 1/',
                    $string->testMessage("38"));
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
            $this->assertWantedPattern(
                    '/differs at character 1/',
                    $string->testMessage("38"));
            $this->assertWantedPattern(
                    '/differs by type/',
                    $string->testMessage(37));
        }
    }
    
    class TestOfPatterns extends UnitTestCase {
        function TestOfPatterns() {
            $this->UnitTestCase();
        }
        function testWanted() {
            $pattern = &new WantedPatternAssertion('/hello/i');
            $this->assertTrue($pattern->test("Hello world"));
            $this->assertFalse($pattern->test("Goodbye world"));
       }
        function testUnwanted() {
            $pattern = &new UnwantedPatternAssertion('/hello/i');
            $this->assertFalse($pattern->test("Hello world"));
            $this->assertTrue($pattern->test("Goodbye world"));
        }
    }
?>