<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'expectation.php');

    class TestOfExpectationFormatting extends UnitTestCase {
        function TestOfExpectationFormatting() {
            $this->UnitTestCase();
        }
        function testClipping() {
            $this->assertEqual(
                    SimpleExpectation::clipString("Hello", 6),
                    "Hello",
                    "Hello, 6->%s");
            $this->assertEqual(
                    SimpleExpectation::clipString("Hello", 5),
                    "Hello",
                    "Hello, 5->%s");
            $this->assertEqual(
                    SimpleExpectation::clipString("Hello world", 3),
                    "Hel...",
                    "Hello world, 3->%s");
            $this->assertEqual(
                    SimpleExpectation::clipString("Hello world", 6, 3),
                    "Hello ...",
                    "Hello world, 6, 3->%s");
            $this->assertEqual(
                    SimpleExpectation::clipString("Hello world", 3, 6),
                    "...o w...",
                    "Hello world, 3, 6->%s");
            $this->assertEqual(
                    SimpleExpectation::clipString("Hello world", 4, 11),
                    "...orld",
                    "Hello world, 4, 11->%s");
            $this->assertEqual(
                    SimpleExpectation::clipString("Hello world", 4, 12),
                    "...orld",
                    "Hello world, 4, 12->%s");
        }
    }

    class TestOfEquality extends UnitTestCase {
        function TestOfEquality() {
            $this->UnitTestCase();
        }
        function testBoolean() {
            $is_true = &new EqualExpectation(true);
            $this->assertTrue($is_true->test(true));
            $this->assertFalse($is_true->test(false));
            $this->assertWantedPattern(
                    '/equal expectation.*?boolean: true/i',
                    $is_true->testMessage(true));
            $this->assertWantedPattern(
                    '/fails.*?boolean.*?boolean/i',
                    $is_true->testMessage(false));
        }
        function testStringMatch() {
            $hello = &new EqualExpectation("Hello");
            $this->assertTrue($hello->test("Hello"));
            $this->assertFalse($hello->test("Goodbye"));
            $this->assertWantedPattern('/Equal expectation.*?Hello/', $hello->testMessage("Hello"));
            $this->assertWantedPattern('/fails/', $hello->testMessage("Goodbye"));
            $this->assertWantedPattern('/fails.*?goodbye/i', $hello->testMessage("Goodbye"));
        }
        function testStringPosition() {
            $comparisons = array(
                    "ab" => 2,
                    "a" => 1,
                    "abcz" => 3,
                    "abz" => 2,
                    "az" => 1,
                    "z" => 0);
            $str = &new EqualExpectation("abc");
            foreach ($comparisons as $compare => $position) {
                $this->assertWantedPattern(
                        "/at character $position/",
                        $str->testMessage($compare));
            }
            $str = &new EqualExpectation("abcd");
            foreach ($comparisons as $compare => $position) {
                $this->assertWantedPattern(
                        "/at character $position/",
                        $str->testMessage($compare));
            }
        }
        function testInteger() {
            $fifteen = &new EqualExpectation(15);
            $this->assertTrue($fifteen->test(15));
            $this->assertFalse($fifteen->test(14));
            $this->assertWantedPattern(
                    '/equal expectation.*?15/i',
                    $fifteen->testMessage(15));
            $this->assertWantedPattern(
                    '/fails.*?15.*?14/i',
                    $fifteen->testMessage(14));
        }
        function testFloat() {
            $pi = &new EqualExpectation(3.14);
            $this->assertTrue($pi->test(3.14));
            $this->assertFalse($pi->test(3.15));
            $this->assertWantedPattern(
                    '/float.*?3\.14/i',
                    $pi->testMessage(3.14));
            $this->assertWantedPattern(
                    '/fails.*?3\.14.*?3\.15/i',
                    $pi->testMessage(3.15));
        }
        function testArray() {
            $colours = &new EqualExpectation(array("r", "g", "b"));
            $this->assertTrue($colours->test(array("r", "g", "b")));
            $this->assertFalse($colours->test(array("g", "b", "r")));
            $this->assertEqual(
                    $colours->testMessage(array("r", "g", "b")),
                    "Equal expectation [Array: 3 items]");
            $this->assertWantedPattern('/fails/', $colours->testMessage(array("r", "g", "z")));
            $this->assertWantedPattern(
                    '/\[2\] at character 0/',
                    $colours->testMessage(array("r", "g", "z")));
            $this->assertWantedPattern(
                    '/key.*? does not match/',
                    $colours->testMessage(array("r", "g")));
            $this->assertWantedPattern(
                    '/key.*? does not match/',
                    $colours->testMessage(array("r", "g", "b", "z")));
        }
        function testHash() {
            $blue = &new EqualExpectation(array("r" => 0, "g" => 0, "b" => 255));
            $this->assertTrue($blue->test(array("r" => 0, "g" => 0, "b" => 255)));
            $this->assertFalse($blue->test(array("r" => 0, "g" => 255, "b" => 0)));
            $this->assertWantedPattern(
                    '/array.*?3 items/i',
                    $blue->testMessage(array("r" => 0, "g" => 0, "b" => 255)));
            $this->assertWantedPattern(
                    '/fails.*?\[b\]/',
                    $blue->testMessage(array("r" => 0, "g" => 0, "b" => 254)));
        }
        function testNestedHash() {
            $tree = &new EqualExpectation(array(
                    "a" => 1,
                    "b" => array(
                            "c" => 2,
                            "d" => "Three")));
            $this->assertWantedPattern(
                    '/member.*?\[b\].*?\[d\].*?at character 5/',
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
            $not_hello = &new NotEqualExpectation("Hello");
            $this->assertTrue($not_hello->test("Goodbye"));
            $this->assertFalse($not_hello->test("Hello"));
            $this->assertWantedPattern(
                    '/at character 0/',
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
            $string = &new IdenticalExpectation("37");
            $this->assertTrue($string->test("37"));
            $this->assertFalse($string->test(37));
            $this->assertFalse($string->test("38"));
            $this->assertWantedPattern(
                    '/identical.*?string.*?37/i',
                    $string->testMessage("37"));
            $this->assertWantedPattern(
                    '/fails.*?37/',
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
            $string = &new NotIdenticalExpectation("37");
            $this->assertTrue($string->test("38"));
            $this->assertTrue($string->test(37));
            $this->assertFalse($string->test("37"));
            $this->assertWantedPattern(
                    '/at character 1/',
                    $string->testMessage("38"));
            $this->assertWantedPattern(
                    '/fails.*?type/',
                    $string->testMessage(37));
        }
    }
    
    class TestOfPatterns extends UnitTestCase {
        function TestOfPatterns() {
            $this->UnitTestCase();
        }
        function testWanted() {
            $pattern = &new WantedPatternExpectation('/hello/i');
            $this->assertTrue($pattern->test("Hello world"));
            $this->assertFalse($pattern->test("Goodbye world"));
       }
        function testUnwanted() {
            $pattern = &new UnwantedPatternExpectation('/hello/i');
            $this->assertFalse($pattern->test("Hello world"));
            $this->assertTrue($pattern->test("Goodbye world"));
        }
    }
?>