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
            $builder = &new SimplePageBuilder($parser, new MockSimplePage($this));
            $this->assertTrue($builder->parse("<html></html>"));
            $parser->tally();
        }
        function testBadLink() {
            $parser = &new MockSimpleSaxParser($this);
            $parser->setReturnValue("parse", true);
            $page = &new MockSimplePage($this);
            $page->expectCallCount("addLink", 0);
            $builder = &new SimplePageBuilder($parser, $page);
            $this->assertFalse($builder->endElement("a"));
            $page->tally();
        }
        function testLink() {
            $parser = &new MockSimpleSaxParser($this);
            $parser->setReturnValue("parse", true);
            $page = &new MockSimplePage($this);
            $page->expectArguments("addLink", array("http://somewhere", "Label"));
            $page->expectCallCount("addLink", 1);
            $builder = &new SimplePageBuilder($parser, $page);
            $this->assertTrue($builder->startElement(
                    "a",
                    array("href" => "http://somewhere")));
            $this->assertTrue($builder->addContent("Label"));
            $this->assertTrue($builder->endElement("a"));
            $page->tally();
        }
        function testLinkExtraction() {
            $parser = &new MockSimpleSaxParser($this);
            $parser->setReturnValue("parse", true);
            $page = &new MockSimplePage($this);
            $page->expectArguments("addLink", array("http://somewhere", "Label"));
            $page->expectCallCount("addLink", 1);
            $builder = &new SimplePageBuilder($parser, $page);
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
            $parser = &new MockSimpleSaxParser($this);
            $parser->setReturnValue("parse", true);
            $page = &new MockSimplePage($this);
            $page->expectArgumentsAt(0, "addLink", array("http://somewhere", "1"));
            $page->expectArgumentsAt(1, "addLink", array("http://elsewhere", "2"));
            $page->expectCallCount("addLink", 2);
            $builder = &new SimplePageBuilder($parser, $page);
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
        function testAddExternalLink() {
            $page = new SimplePage("");
            $page->addLink("http://somewhere", "Label");
            $this->assertEqual($page->getAbsoluteLinks(), array("http://somewhere"), "abs->%s");
            $this->assertIdentical($page->getRelativeLinks(), array(), "rel->%s");
            $this->assertEqual($page->getUrls("Label"), array("http://somewhere"));
        }
        function testAddStrictInternalLink() {
            $page = new SimplePage("");
            $page->addLink("./somewhere.php", "Label", true);
            $this->assertEqual($page->getAbsoluteLinks(), array(), "abs->%s");
            $this->assertIdentical($page->getRelativeLinks(), array("./somewhere.php"), "rel->%s");
            $this->assertEqual($page->getUrls("Label"), array("./somewhere.php"));
        }
        function testAddInternalLink() {
            $page = new SimplePage("");
            $page->addLink("somewhere.php", "Label");
            $this->assertEqual($page->getAbsoluteLinks(), array(), "abs->%s");
            $this->assertIdentical($page->getRelativeLinks(), array("./somewhere.php"), "rel->%s");
            $this->assertEqual($page->getUrls("Label"), array("./somewhere.php"));
        }
    }
?>