<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'page.php');
    require_once(SIMPLE_TEST . 'parser.php');
    
    Mock::generate("SimpleSaxParser");
    Mock::generate("SimplePage");
    
    class TestOfTag extends UnitTestCase {
        function TestOfTag() {
            $this->UnitTestCase();
        }
        function testStartValues() {
            $tag = new SimpleTag("hello", array("a" => 1, "b" => true));
            $this->assertEqual($tag->getname(), "hello");
            $this->assertIdentical($tag->getAttribute("a"), "1");
            $this->assertIdentical($tag->getAttribute("b"), true);
            $this->assertIdentical($tag->getAttribute("c"), false);
            $this->assertIdentical($tag->getContent(), "");
        }
        function testContent() {
            $tag = new SimpleTag("a", array());
            $tag->addContent("Hello");
            $tag->addContent("World");
            $this->assertEqual($tag->getContent(), "HelloWorld");
        }
    }
    
    class TestOfPageBuilder extends UnitTestCase {
        function TestOfPageBuilder() {
            $this->UnitTestCase();
        }
        function testParserChaining() {
            $parser = &new MockSimpleSaxParser($this);
            $parser->setReturnValue("parse", true);
            $parser->expectArguments("parse", array("<html></html>"));
            $parser->expectCallCount("parse", 1);
            $builder = &new SimplePageBuilder(new MockSimplePage($this));
            $this->assertTrue($builder->parse("<html></html>", $parser));
            $parser->tally();
        }
        function testBadLink() {
            $page = &new MockSimplePage($this);
            $page->expectCallCount("acceptTag", 0);
            $builder = &new SimplePageBuilder($page);
            $this->assertFalse($builder->endElement("a"));
            $page->tally();
        }
        function testLink() {
            $tag = new SimpleTag("a", array("href" => "http://somewhere"));
            $tag->addContent("Label");
            $page = &new MockSimplePage($this);
            $page->expectArguments("acceptTag", array($tag));
            $page->expectCallCount("acceptTag", 1);
            $builder = &new SimplePageBuilder($page);
            $this->assertTrue($builder->startElement(
                    "a",
                    array("href" => "http://somewhere")));
            $this->assertTrue($builder->addContent("Label"));
            $this->assertTrue($builder->endElement("a"));
            $page->tally();
        }
        function testLinkWithId() {
            $tag = new SimpleTag("a", array("href" => "http://somewhere", "id" => "44"));
            $tag->addContent("Label");
            $page = &new MockSimplePage($this);
            $page->expectArguments("acceptTag", array($tag));
            $page->expectCallCount("acceptTag", 1);
            $builder = &new SimplePageBuilder($page);
            $this->assertTrue($builder->startElement(
                    "a",
                    array("href" => "http://somewhere", "id" => "44")));
            $this->assertTrue($builder->addContent("Label"));
            $this->assertTrue($builder->endElement("a"));
            $page->tally();
        }
        function testLinkExtraction() {
            $tag = new SimpleTag("a", array("href" => "http://somewhere"));
            $tag->addContent("Label");
            $page = &new MockSimplePage($this);
            $page->expectArguments("acceptTag", array($tag));
            $page->expectCallCount("acceptTag", 1);
            $builder = &new SimplePageBuilder($page);
            $this->assertTrue($builder->addContent("Starting stuff"));
            $this->assertTrue($builder->startElement(
                    "a",
                    array("href" => "http://somewhere")));
            $this->assertTrue($builder->addContent("Label"));
            $this->assertTrue($builder->endElement("a"));
            $this->assertTrue($builder->addContent("Trailing stuff"));
            $page->tally();
        }
        function testMultipleLinks() {
            $a1 = new SimpleTag("a", array("href" => "http://somewhere"));
            $a1->addContent("1");
            $a2 = new SimpleTag("a", array("href" => "http://elsewhere"));
            $a2->addContent("2");
            $page = &new MockSimplePage($this);
            $page->expectArgumentsAt(0, "acceptTag", array($a1));
            $page->expectArgumentsAt(1, "acceptTag", array($a2));
            $page->expectCallCount("acceptTag", 2);
            $builder = &new SimplePageBuilder($page);
            $builder->startElement("a", array("href" => "http://somewhere"));
            $builder->addContent("1");
            $builder->endElement("a");
            $builder->addContent("Padding");
            $builder->startElement("a", array("href" => "http://elsewhere"));
            $builder->addContent("2");
            $builder->endElement("a");
            $page->tally();
        }
        function testTitle() {
            $tag = new SimpleTag("title", array());
            $tag->addContent("HereThere");
            $page = &new MockSimplePage($this);
            $page->expectArguments("acceptTag", array($tag));
            $page->expectCallCount("acceptTag", 1);
            $builder = &new SimplePageBuilder($page);
            $builder->startElement("title", array());
            $builder->addContent("Here");
            $builder->addContent("There");
            $builder->endElement("title");
            $page->tally();
        }
    }
    
    class TestSimplePage extends SimplePage {
        var $_parser;
        var $_builder;
        
        function TestSimplePage($raw, &$parser, &$builder) {
            $this->_parser = &$parser;
            $this->_builder = &$builder;
            $this->SimplePage($raw);
        }
        function &_createParser() {
            return $this->_parser;
        }
        function &_createBuilder() {
            return $this->_builder;
        }
    }
    
    Mock::generate("SimplePageBuilder");
    
    class TestOfPageParsing extends UnitTestCase {
        function TestOfPageParsing() {
            $this->UnitTestCase();
        }
        function testParse() {
            $parser = &new MockSimpleSaxParser($this);
            $builder = &new MockSimplePageBuilder($this);
            $builder->expectArguments("parse", array("stuff", "*"));
            $builder->expectCallCount("parse", 1);
            $page = &new TestSimplePage("stuff", $parser, $builder);
            $builder->tally();
        }
    }

    class TestOfHtmlPage extends UnitTestCase {
        function TestOfHtmlPage() {
            $this->UnitTestCase();
        }
        function testNoLinks() {
            $page = new SimplePage("");
            $this->assertIdentical($page->getAbsoluteLinks(), array(), "abs->%s");
            $this->assertIdentical($page->getRelativeLinks(), array(), "rel->%s");
            $this->assertIdentical($page->getUrls("Label"), array());
        }
        function testAddAbsoluteLink() {
            $link = new SimpleTag("a", array("href" => "http://somewhere"));
            $link->addContent("Label");
            $page = new SimplePage("");
            $page->AcceptTag($link);
            $this->assertEqual($page->getAbsoluteLinks(), array("http://somewhere"), "abs->%s");
            $this->assertIdentical($page->getRelativeLinks(), array(), "rel->%s");
            $this->assertEqual($page->getUrls("Label"), array("http://somewhere"));
        }
        function testAddStrictRelativeLink() {
            $link = new SimpleTag("a", array("href" => "./somewhere.php"));
            $link->addContent("Label");
            $page = new SimplePage("");
            $page->AcceptTag($link);
            $this->assertEqual($page->getAbsoluteLinks(), array(), "abs->%s");
            $this->assertIdentical($page->getRelativeLinks(), array("./somewhere.php"), "rel->%s");
            $this->assertEqual($page->getUrls("Label"), array("./somewhere.php"));
        }
        function testAddRelativeLink() {
            $link = new SimpleTag("a", array("href" => "somewhere.php"));
            $link->addContent("Label");
            $page = new SimplePage("");
            $page->AcceptTag($link);
            $this->assertEqual($page->getAbsoluteLinks(), array(), "abs->%s");
            $this->assertIdentical($page->getRelativeLinks(), array("./somewhere.php"), "rel->%s");
            $this->assertEqual($page->getUrls("Label"), array("./somewhere.php"));
        }
        function testLinkIds() {
            $link = new SimpleTag("a", array("href" => "./somewhere.php", "id" => 33));
            $link->addContent("Label");
            $page = new SimplePage("");
            $page->AcceptTag($link);
            $this->assertEqual($page->getUrls("Label"), array("./somewhere.php"));
            $this->assertFalse($page->getUrlById(0));
            $this->assertEqual($page->getUrlById(33), "./somewhere.php");
        }
        function testTitleSetting() {
            $title = new SimpleTag("title", array());
            $title->addContent("Title");
            $page = new SimplePage("");
            $page->AcceptTag($title);
            $this->assertEqual($page->getTitle(), "Title");
        }
    }
    
    class TestOfPageScraping extends UnitTestCase {
        function TestOfPageScraping() {
            $this->UnitTestCase();
        }
        function testEmptyPage() {
            $page = &new SimplePage("");
            $this->assertIdentical($page->getAbsoluteLinks(), array());
            $this->assertIdentical($page->getRelativeLinks(), array());
            $this->assertIdentical($page->getTitle(), false);
        }
        function testUninterestingPage() {
            $page = &new SimplePage("<html><body><p>Stuff</p></body></html>");
            $this->assertIdentical($page->getAbsoluteLinks(), array());
            $this->assertIdentical($page->getRelativeLinks(), array());
        }
        function testLinksPage() {
            $raw = '<html>';
            $raw .= '<a href="there.html">There</a>';
            $raw .= '<a href="http://there.com/that.html" id="0">That page</a>';
            $raw .= '</html>';
            $page = &new SimplePage($raw);
            $this->assertIdentical(
                    $page->getAbsoluteLinks(),
                    array("http://there.com/that.html"));
            $this->assertIdentical(
                    $page->getRelativeLinks(),
                    array("./there.html"));
            $this->assertIdentical($page->getUrls("There"), array("./there.html"));
            $this->assertEqual($page->getUrlById(0), "http://there.com/that.html");
        }
        function testTitle() {
            $page = &new SimplePage("<html><head><title>Me</title></head></html>");
            $this->assertEqual($page->getTitle(), "Me");
        }
    }
?>