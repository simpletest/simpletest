<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'page.php');
    require_once(SIMPLE_TEST . 'parser.php');
    
    Mock::generate("SimpleSaxParser");
    Mock::generate("SimplePage");
    
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
            $page->expectCallCount("addLink", 0);
            $builder = &new SimplePageBuilder($page);
            $this->assertFalse($builder->endElement("a"));
            $page->tally();
        }
        function testLink() {
            $page = &new MockSimplePage($this);
            $page->expectArguments("addLink", array("http://somewhere", "Label", "*"));
            $page->expectCallCount("addLink", 1);
            $builder = &new SimplePageBuilder($page);
            $this->assertTrue($builder->startElement(
                    "a",
                    array("href" => "http://somewhere")));
            $this->assertTrue($builder->addContent("Label"));
            $this->assertTrue($builder->endElement("a"));
            $page->tally();
        }
        function testLinkWithId() {
            $page = &new MockSimplePage($this);
            $page->expectArguments("addLink", array("http://somewhere", "Label", "44"));
            $page->expectCallCount("addLink", 1);
            $builder = &new SimplePageBuilder($page);
            $this->assertTrue($builder->startElement(
                    "a",
                    array("href" => "http://somewhere", "id" => "44")));
            $this->assertTrue($builder->addContent("Label"));
            $this->assertTrue($builder->endElement("a"));
            $page->tally();
        }
        function testLinkExtraction() {
            $page = &new MockSimplePage($this);
            $page->expectArguments("addLink", array("http://somewhere", "Label", "*"));
            $page->expectCallCount("addLink", 1);
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
            $page = &new MockSimplePage($this);
            $page->expectArgumentsAt(0, "addLink", array("http://somewhere", "1", "*"));
            $page->expectArgumentsAt(1, "addLink", array("http://elsewhere", "2", "*"));
            $page->expectCallCount("addLink", 2);
            $builder = &new SimplePageBuilder($page);
            $this->assertTrue($builder->startElement(
                    "a",
                    array("href" => "http://somewhere")));
            $this->assertTrue($builder->addContent("1"));
            $this->assertTrue($builder->endElement("a"));
            $this->assertTrue($builder->addContent("Padding"));
            $this->assertTrue($builder->startElement(
                    "a",
                    array("href" => "http://elsewhere")));
            $this->assertTrue($builder->addContent("2"));
            $this->assertTrue($builder->endElement("a"));
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
            $page = new SimplePage("");
            $page->addLink("http://somewhere", "Label", false);
            $this->assertEqual($page->getAbsoluteLinks(), array("http://somewhere"), "abs->%s");
            $this->assertIdentical($page->getRelativeLinks(), array(), "rel->%s");
            $this->assertEqual($page->getUrls("Label"), array("http://somewhere"));
        }
        function testAddStrictRelativeLink() {
            $page = new SimplePage("");
            $page->addLink("./somewhere.php", "Label", false, true);
            $this->assertEqual($page->getAbsoluteLinks(), array(), "abs->%s");
            $this->assertIdentical($page->getRelativeLinks(), array("./somewhere.php"), "rel->%s");
            $this->assertEqual($page->getUrls("Label"), array("./somewhere.php"));
        }
        function testAddRelativeLink() {
            $page = new SimplePage("");
            $page->addLink("somewhere.php", "Label", false);
            $this->assertEqual($page->getAbsoluteLinks(), array(), "abs->%s");
            $this->assertIdentical($page->getRelativeLinks(), array("./somewhere.php"), "rel->%s");
            $this->assertEqual($page->getUrls("Label"), array("./somewhere.php"));
        }
        function testLinkIds() {
            $page = new SimplePage("");
            $page->addLink("somewhere.php", "Label", 33);
            $this->assertEqual($page->getUrls("Label"), array("./somewhere.php"));
            $this->assertFalse($page->getUrlById(0));
            $this->assertEqual($page->getUrlById(33), "./somewhere.php");
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
    }
?>