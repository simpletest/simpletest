<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'parser.php');
    Mock::generate("HtmlPage");

    class TestOfLexer extends UNitTestCase {
        function TestOfLexer() {
            $this->UnitTestCase();
        }
        function testEmptyPage() {
            $lexer = &new SimpleLexer();
            $this->assertEqual(count($lexer->parse("")), 0);
        }
        function testNoPatterns() {
            $lexer = &new SimpleLexer();
            $this->assertEqual($lexer->parse("abcdef"), array("abcdef"));
        }
        function testSinglePattern() {
            $lexer = &new SimpleLexer();
            $lexer->addPattern("a+");
            $this->assertEqual(
                    $lexer->parse("aaaxayyyaxaaaz"),
                    array("aaa", "x", "a", "yyy", "a", "x", "aaa", "z"));
        }
        function testMultiplePattern() {
            $lexer = &new SimpleLexer();
            $lexer->addPattern("a+");
            $lexer->addPattern("b+");
            $this->assertEqual(
                    $lexer->parse("ababbxbaxxxxxxax"),
                    array("a", "b", "a", "bb", "x", "b", "a", "xxxxxx", "a", "x"));
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