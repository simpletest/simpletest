<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'parser.php');

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
    
    class TestParser {
        function TestParser() {
        }
        function accept() {
        }
        function a() {
        }
        function b() {
        }
    }
    Mock::generate('TestParser');

    class TestOfLexer extends UnitTestCase {
        function TestOfLexer() {
            $this->UnitTestCase();
        }
        function testNoPatterns() {
            $handler = &new MockTestParser($this);
            $handler->expectMaximumCallCount("accept", 0);
            $handler->setReturnValue("accept", true);
            $lexer = &new SimpleLexer($handler);
            $this->assertFalse($lexer->parse("abcdef"));
        }
        function testEmptyPage() {
            $handler = &new MockTestParser($this);
            $handler->expectMaximumCallCount("accept", 0);
            $handler->setReturnValue("accept", true);
            $handler->expectMaximumCallCount("accept", 0);
            $handler->setReturnValue("accept", true);
            $lexer = &new SimpleLexer($handler);
            $lexer->addPattern("a+");
            $this->assertTrue($lexer->parse(""));
        }
        function testSinglePattern() {
            $handler = &new MockTestParser($this);
            $handler->expectArgumentsSequence(0, "accept", array("aaa", LEXER_MATCHED));
            $handler->expectArgumentsSequence(1, "accept", array("x", LEXER_UNMATCHED));
            $handler->expectArgumentsSequence(2, "accept", array("a", LEXER_MATCHED));
            $handler->expectArgumentsSequence(3, "accept", array("yyy", LEXER_UNMATCHED));
            $handler->expectArgumentsSequence(4, "accept", array("a", LEXER_MATCHED));
            $handler->expectArgumentsSequence(5, "accept", array("x", LEXER_UNMATCHED));
            $handler->expectArgumentsSequence(6, "accept", array("aaa", LEXER_MATCHED));
            $handler->expectArgumentsSequence(7, "accept", array("z", LEXER_UNMATCHED));
            $handler->expectCallCount("accept", 8);
            $handler->setReturnValue("accept", true);
            $lexer = &new SimpleLexer($handler);
            $lexer->addPattern("a+");
            $this->assertTrue($lexer->parse("aaaxayyyaxaaaz"));
            $handler->tally();
        }
        function testMultiplePattern() {
            $handler = &new MockTestParser($this);
            $target = array("a", "b", "a", "bb", "x", "b", "a", "xxxxxx", "a", "x");
            for ($i = 0; $i < count($target); $i++) {
                $handler->expectArgumentsSequence($i, "accept", array($target[$i], '*'));
            }
            $handler->expectCallCount("accept", count($target));
            $handler->setReturnValue("accept", true);
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
            $handler = &new MockTestParser($this);
            $handler->expectArgumentsSequence(0, "a", array("a", LEXER_MATCHED));
            $handler->expectArgumentsSequence(1, "a", array("b", LEXER_UNMATCHED));
            $handler->expectArgumentsSequence(2, "a", array("aa", LEXER_MATCHED));
            $handler->expectArgumentsSequence(3, "a", array("bxb", LEXER_UNMATCHED));
            $handler->expectArgumentsSequence(4, "a", array("aaa", LEXER_MATCHED));
            $handler->expectArgumentsSequence(5, "a", array("x", LEXER_UNMATCHED));
            $handler->expectArgumentsSequence(6, "a", array("aaaa", LEXER_MATCHED));
            $handler->expectArgumentsSequence(7, "a", array("x", LEXER_UNMATCHED));
            $handler->expectCallCount("a", 8);
            $handler->setReturnValue("a", true);
            $lexer = &new SimpleLexer($handler, "a");
            $lexer->addPattern("a+", "a");
            $lexer->addPattern("b+", "b");
            $this->assertTrue($lexer->parse("abaabxbaaaxaaaax"));
            $handler->tally();
        }
        function testModeChange() {
            $handler = &new MockTestParser($this);
            $handler->expectArgumentsSequence(0, "a", array("a", LEXER_MATCHED));
            $handler->expectArgumentsSequence(1, "a", array("b", LEXER_UNMATCHED));
            $handler->expectArgumentsSequence(2, "a", array("aa", LEXER_MATCHED));
            $handler->expectArgumentsSequence(3, "a", array("b", LEXER_UNMATCHED));
            $handler->expectArgumentsSequence(4, "a", array("aaa", LEXER_MATCHED));
            $handler->expectArgumentsSequence(0, "b", array(":", LEXER_ENTER));
            $handler->expectArgumentsSequence(1, "b", array("a", LEXER_UNMATCHED));
            $handler->expectArgumentsSequence(2, "b", array("b", LEXER_MATCHED));
            $handler->expectArgumentsSequence(3, "b", array("a", LEXER_UNMATCHED));
            $handler->expectArgumentsSequence(4, "b", array("bb", LEXER_MATCHED));
            $handler->expectArgumentsSequence(5, "b", array("a", LEXER_UNMATCHED));
            $handler->expectArgumentsSequence(6, "b", array("bbb", LEXER_MATCHED));
            $handler->expectArgumentsSequence(7, "b", array("a", LEXER_UNMATCHED));
            $handler->expectCallCount("a", 5);
            $handler->expectCallCount("b", 8);
            $handler->setReturnValue("a", true);
            $handler->setReturnValue("b", true);
            $lexer = &new SimpleLexer($handler, "a");
            $lexer->addPattern("a+", "a");
            $lexer->addEntryPattern(":", "a", "b");
            $lexer->addPattern("b+", "b");
            $this->assertTrue($lexer->parse("abaabaaa:ababbabbba"));
            $handler->tally();
        }
        function testNesting() {
            $handler = &new MockTestParser($this);
            $handler->setReturnValue("a", true);
            $handler->setReturnValue("b", true);
            $handler->expectArgumentsSequence(0, "a", array("aa", LEXER_MATCHED));
            $handler->expectArgumentsSequence(1, "a", array("b", LEXER_UNMATCHED));
            $handler->expectArgumentsSequence(2, "a", array("aa", LEXER_MATCHED));
            $handler->expectArgumentsSequence(3, "a", array("b", LEXER_UNMATCHED));
            $handler->expectArgumentsSequence(0, "b", array("(", LEXER_ENTER));
            $handler->expectArgumentsSequence(1, "b", array("bb", LEXER_MATCHED));
            $handler->expectArgumentsSequence(2, "b", array("a", LEXER_UNMATCHED));
            $handler->expectArgumentsSequence(3, "b", array("bb", LEXER_MATCHED));
            $handler->expectArgumentsSequence(4, "b", array(")", LEXER_EXIT));
            $handler->expectArgumentsSequence(4, "a", array("aa", LEXER_MATCHED));
            $handler->expectArgumentsSequence(5, "a", array("b", LEXER_UNMATCHED));
            $handler->expectCallCount("a", 6);
            $handler->expectCallCount("b", 5);
            $lexer = &new SimpleLexer($handler, "a");
            $lexer->addPattern("a+", "a");
            $lexer->addEntryPattern("(", "a", "b");
            $lexer->addPattern("b+", "b");
            $lexer->addExitPattern(")", "b");
            $this->assertTrue($lexer->parse("aabaab(bbabb)aab"));
            $handler->tally();
        }
        function testSingular() {
            $handler = &new MockTestParser($this);
            $handler->setReturnValue("a", true);
            $handler->setReturnValue("b", true);
            $handler->expectArgumentsSequence(0, "a", array("aa", LEXER_MATCHED));
            $handler->expectArgumentsSequence(1, "a", array("aa", LEXER_MATCHED));
            $handler->expectArgumentsSequence(2, "a", array("xx", LEXER_UNMATCHED));
            $handler->expectArgumentsSequence(3, "a", array("xx", LEXER_UNMATCHED));
            $handler->expectArgumentsSequence(0, "b", array("b", LEXER_SPECIAL));
            $handler->expectArgumentsSequence(1, "b", array("bbb", LEXER_SPECIAL));
            $handler->expectCallCount("a", 4);
            $handler->expectCallCount("b", 2);
            $lexer = &new SimpleLexer($handler, "a");
            $lexer->addPattern("a+", "a");
            $lexer->addSpecialPattern("b+", "a", "b");
            $this->assertTrue($lexer->parse("aabaaxxbbbxx"));
            $handler->tally();
        }
        function testUnwindTooFar() {
            $handler = &new MockTestParser($this);
            $handler->setReturnValue("a", true);
            $handler->expectArgumentsSequence(0, "a", array("aa", LEXER_MATCHED));
            $handler->expectArgumentsSequence(1, "a", array(")", LEXER_EXIT));
            $handler->expectCallCount("a", 2);
            $lexer = &new SimpleLexer($handler, "a");
            $lexer->addPattern("a+", "a");
            $lexer->addExitPattern(")", "a");
            $this->assertFalse($lexer->parse("aa)aa"));
            $handler->tally();
        }
    }

    class TestOfLexerHandlers extends UnitTestCase {
        function TestOfLexerHandlers() {
            $this->UnitTestCase();
        }
        function testModeMapping() {
            $handler = &new MockTestParser($this);
            $handler->setReturnValue("a", true);
            $handler->expectArgumentsSequence(0, "a", array("aa", LEXER_MATCHED));
            $handler->expectArgumentsSequence(1, "a", array("(", LEXER_ENTER));
            $handler->expectArgumentsSequence(2, "a", array("bb", LEXER_MATCHED));
            $handler->expectArgumentsSequence(3, "a", array("a", LEXER_UNMATCHED));
            $handler->expectArgumentsSequence(4, "a", array("bb", LEXER_MATCHED));
            $handler->expectArgumentsSequence(5, "a", array(")", LEXER_EXIT));
            $handler->expectArgumentsSequence(6, "a", array("b", LEXER_UNMATCHED));
            $handler->expectCallCount("a", 7);
            $lexer = &new SimpleLexer($handler, "mode_a");
            $lexer->addPattern("a+", "mode_a");
            $lexer->addEntryPattern("(", "mode_a", "mode_b");
            $lexer->addPattern("b+", "mode_b");
            $lexer->addExitPattern(")", "mode_b");
            $lexer->mapHandler("mode_a", "a");
            $lexer->mapHandler("mode_b", "a");
            $this->assertTrue($lexer->parse("aa(bbabb)b"));
            $handler->tally();
        }
    }
    
    Mock::generate("HtmlSaxParser");
    
    class TestOfHtmlLexer extends UnitTestCase {
        var $_handler;
        var $_lexer;
        
        function TestOfHtmlLexer() {
            $this->UnitTestCase();
        }
        function setUp() {
            $this->_handler = &new MockHtmlSaxParser($this);
            $this->_handler->setReturnValue("acceptStartToken", true);
            $this->_handler->setReturnValue("acceptEndToken", true);
            $this->_handler->setReturnValue("acceptAttributeToken", true);
            $this->_handler->setReturnValue("acceptEntityToken", true);
            $this->_handler->setReturnValue("acceptTextToken", true);
            $this->_handler->setReturnValue("ignore", true);
            $this->_lexer = &HtmlSaxParser::createLexer($this->_handler, "ignore");
        }
        function tearDown() {
            $this->_handler->tally();
        }
        function testUninteresting() {
            $this->_handler->expectArguments("ignore", array("<html></html>", "*"));
            $this->_handler->expectCallCount("ignore", 1);
            $this->assertTrue($this->_lexer->parse("<html></html>"));
        }
        function testEmptyLink() {
            $this->_handler->expectArgumentsSequence(0, "acceptStartToken", array("<a", "*"));
            $this->_handler->expectArgumentsSequence(1, "acceptStartToken", array(">", "*"));
            $this->_handler->expectCallCount("acceptStartToken", 2);
            $this->_handler->expectArgumentsSequence(0, "acceptEndToken", array("</a>", "*"));
            $this->_handler->expectCallCount("acceptEndToken", 1);
            $this->assertTrue($this->_lexer->parse("<html><a></a></html>"));
        }
    }
?>