<?php
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'parser.php');
    Mock::generate("HtmlPage");

    class TestOfLexer extends UnitTestCase {
        function TestOfLexer() {
            $this->UnitTestCase();
        }
        function testEmptyPage() {
            $page = &new MockHtmlPage($this);
            $page->setExpectedCallCount("addLink", 0);
            $page->setExpectedCallCount("addFormElement", 0);
            $parser = &new HtmlParser();
            $this->assertTrue($parser->parse("", $page));
            $page->tally();
        }
    }
?>