<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'parser.php');
    Mock::generate("HtmlPage");
    Mock::generate("TokenHandler");

    class TestOfParallelRegex extends UnitTestCase {
        function TestOfParallelRegex() {
            $this->UnitTestCase();
        }
        function testNoPatterns() {
            $regex = &new ParallelRegex();
            $this->assertFalse($regex->match("Hello", $match));
            $this->assertEqual($match, "");
        }
        function testNoSubject() {
            $regex = &new ParallelRegex();
            $regex->addPattern(".*");
            $this->assertTrue($regex->match("", $match));
            $this->assertEqual($match, "");
        }
        function testMatchAll() {
            $regex = &new ParallelRegex();
            $regex->addPattern(".*");
            $this->assertTrue($regex->match("Hello", $match));
            $this->assertEqual($match, "Hello");
        }
        function testMatchMultiple() {
            $regex = &new ParallelRegex();
            $regex->addPattern("abc");
            $regex->addPattern("ABC");
            $this->assertTrue($regex->match("abcdef", $match));
            $this->assertEqual($match, "abc");
            $this->assertTrue($regex->match("AAABCabcdef", $match));
            $this->assertEqual($match, "ABC");
            $this->assertFalse($regex->match("Hello", $match));
        }
        function testPatternLabels() {
            $regex = &new ParallelRegex();
            $regex->addPattern("abc", "letter");
            $regex->addPattern("123", "number");
            $this->assertIdentical($regex->match("abcdef", $match), "letter");
            $this->assertEqual($match, "abc");
            $this->assertIdentical($regex->match("0123456789", $match), "number");
            $this->assertEqual($match, "123");
        }
    }
    
    class TestOfStateStack extends UnitTestCase {
        function TestOfStateStack() {
            $this->UnitTestCase();
        }
        function testStartState() {
            $stack = &new StateStack("one");
            $this->assertEqual($stack->getCurrent(), "one");
        }
        function testExhaustion() {
            $stack = &new StateStack("one");
            $this->assertFalse($stack->leave());
        }
        function testStateMoves() {
            $stack = &new StateStack("one");
            $stack->enter("two");
            $this->assertEqual($stack->getCurrent(), "two");
            $stack->enter("three");
            $this->assertEqual($stack->getCurrent(), "three");
            $this->assertTrue($stack->leave());
            $this->assertEqual($stack->getCurrent(), "two");
            $stack->enter("third");
            $this->assertEqual($stack->getCurrent(), "third");
            $this->assertTrue($stack->leave());
            $this->assertTrue($stack->leave());
            $this->assertEqual($stack->getCurrent(), "one");
        }
    }

    class TestOfLexer extends UnitTestCase {
        function TestOfLexer() {
            $this->UnitTestCase();
        }
        function testEmptyPage() {
            $handler = &new MockTokenHandler($this);
            $handler->expectMaximumCallCount("acceptToken", 0);
            $handler->setReturnValue("acceptToken", true);
            $handler->expectMaximumCallCount("acceptUnparsed", 0);
            $handler->setReturnValue("acceptUnparsed", true);
            $lexer = &new SimpleLexer($handler);
            $this->assertTrue($lexer->parse(""));
        }
        function testNoPatterns() {
            $handler = &new MockTokenHandler($this);
            $handler->expectMaximumCallCount("acceptToken", 0);
            $handler->setReturnValue("acceptToken", true);
            $handler->expectArgumentsSequence(0, "acceptUnparsed", array("abcdef"));
            $handler->expectCallCount("acceptUnparsed", 1);
            $handler->setReturnValue("acceptUnparsed", true);
            $lexer = &new SimpleLexer($handler);
            $this->assertTrue($lexer->parse("abcdef"));
            $handler->tally();
        }
        function testSinglePattern() {
            $handler = &new MockTokenHandler($this);
            $handler->expectArgumentsSequence(0, "acceptToken", array("aaa"));
            $handler->expectArgumentsSequence(1, "acceptToken", array("a"));
            $handler->expectArgumentsSequence(2, "acceptToken", array("a"));
            $handler->expectArgumentsSequence(3, "acceptToken", array("aaa"));
            $handler->expectCallCount("acceptToken", 4);
            $handler->setReturnValue("acceptToken", true);
            $handler->expectArgumentsSequence(0, "acceptUnparsed", array("x"));
            $handler->expectArgumentsSequence(1, "acceptUnparsed", array("yyy"));
            $handler->expectArgumentsSequence(2, "acceptUnparsed", array("x"));
            $handler->expectArgumentsSequence(3, "acceptUnparsed", array("z"));
            $handler->expectCallCount("acceptUnparsed", 4);
            $handler->setReturnValue("acceptUnparsed", true);
            $lexer = &new SimpleLexer($handler);
            $lexer->addPattern("a+");
            $this->assertTrue($lexer->parse("aaaxayyyaxaaaz"));
            $handler->tally();
        }
        function testMultiplePattern() {
            $handler = &new MockTokenHandler($this);
            $target = array("a", "b", "a", "bb", "b", "a", "a");
            for ($i = 0; $i < count($target); $i++) {
                $handler->expectArgumentsSequence($i, "acceptToken", array($target[$i]));
            }
            $handler->expectCallCount("acceptToken", count($target));
            $handler->setReturnValue("acceptToken", true);
            $handler->expectArgumentsSequence(0, "acceptUnparsed", array("x"));
            $handler->expectArgumentsSequence(1, "acceptUnparsed", array("xxxxxx"));
            $handler->expectArgumentsSequence(2, "acceptUnparsed", array("x"));
            $handler->expectCallCount("acceptUnparsed", 3);
            $handler->setReturnValue("acceptUnparsed", true);
            $lexer = &new SimpleLexer($handler);
            $lexer->addPattern("a+");
            $lexer->addPattern("b+");
            $this->assertTrue($lexer->parse("ababbxbaxxxxxxax"));
            $handler->tally();
        }
    }

    class TestOfLexerModes extends UnitTestCase {
        function TestOfLexerModes() {
            $this->UnitTestCase();
        }
        function testIsolatedPattern() {
            $handler = &new MockTokenHandler($this);
            $handler->expectArgumentsSequence(0, "acceptToken", array("a"));
            $handler->expectArgumentsSequence(1, "acceptToken", array("aa"));
            $handler->expectArgumentsSequence(2, "acceptToken", array("aaa"));
            $handler->expectArgumentsSequence(3, "acceptToken", array("aaaa"));
            $handler->expectCallCount("acceptToken", 4);
            $handler->setReturnValue("acceptToken", true);
            $handler->setReturnValue("acceptUnparsed", true);
            $lexer = &new SimpleLexer($handler, "used");
            $lexer->addPattern("a+", "used");
            $lexer->addPattern("b+", "unused");
            $this->assertTrue($lexer->parse("abaabxbaaaxaaaax"));
            $handler->tally();
        }
        function testModeChange() {
            $handler = &new MockTokenHandler($this);
            $handler->expectArgumentsSequence(0, "acceptToken", array("a"));
            $handler->expectArgumentsSequence(1, "acceptToken", array("aa"));
            $handler->expectArgumentsSequence(2, "acceptToken", array("aaa"));
            $handler->expectArgumentsSequence(3, "acceptToken", array(":"));
            $handler->expectArgumentsSequence(4, "acceptToken", array("b"));
            $handler->expectArgumentsSequence(5, "acceptToken", array("bb"));
            $handler->expectArgumentsSequence(6, "acceptToken", array("bbb"));
            $handler->expectCallCount("acceptToken", 7);
            $handler->setReturnValue("acceptToken", true);
            $handler->setReturnValue("acceptUnparsed", true);
            $lexer = &new SimpleLexer($handler, "a");
            $lexer->addPattern("a+", "a");
            $lexer->addEntryPattern(":", "a", "b");
            $lexer->addPattern("b+", "b");
            $this->assertTrue($lexer->parse("abaabaaa:ababbabbba"));
            $handler->tally();
        }
        function testNesting() {
            $handler = &new MockTokenHandler($this);
            $handler->setReturnValue("acceptToken", true);
            $handler->setReturnValue("acceptUnparsed", true);
            $handler->expectArgumentsSequence(0, "acceptToken", array("aa"));
            $handler->expectArgumentsSequence(1, "acceptToken", array("aa"));
            $handler->expectArgumentsSequence(2, "acceptToken", array("("));
            $handler->expectArgumentsSequence(3, "acceptToken", array("bb"));
            $handler->expectArgumentsSequence(4, "acceptToken", array("bb"));
            $handler->expectArgumentsSequence(5, "acceptToken", array(")"));
            $handler->expectArgumentsSequence(6, "acceptToken", array("aa"));
            $handler->expectCallCount("acceptToken", 7);
            $lexer = &new SimpleLexer($handler, "a");
            $lexer->addPattern("a+", "a");
            $lexer->addEntryPattern("(", "a", "b");
            $lexer->addPattern("b+", "b");
            $lexer->addExitPattern(")", "b");
            $this->assertTrue($lexer->parse("aabaab(bbabb)aab"));
            $handler->tally();
        }
    }
    
    class TestOfParser extends UnitTestCase {
        function TestOfParser() {
            $this->UnitTestCase();
        }
        function testEmptyPage() {
            $page = &new MockHtmlPage($this);
            $page->expectCallCount("addLink", 0);
            $page->expectCallCount("addFormElement", 0);
            $parser = &new HtmlParser();
            $this->assertTrue($parser->parse("", $page));
            $page->tally();
        }
    }
?>