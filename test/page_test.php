<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'page.php');

    class TestOfHtmlPage extends UnitTestCase {
        function TestOfHtmlPage() {
            $this->UnitTestCase();
        }
        function testNoLinks() {
            $page = new HtmlPage();
            $this->assertIdentical($page->getAbsoluteLinks(), array(), "abs->%s");
            $this->assertIdentical($page->getRelativeLinks(), array(), "rel->%s");
            $this->assertIdentical($page->getUrls("Label"), array());
        }
        function testAddExternalLink() {
            $page = new HtmlPage();
            $page->addLink("http://somewhere", "Label");
            $this->assertEqual($page->getAbsoluteLinks(), array("http://somewhere"), "abs->%s");
            $this->assertIdentical($page->getRelativeLinks(), array(), "rel->%s");
            $this->assertEqual($page->getUrls("Label"), array("http://somewhere"));
        }
        function testAddStrictInternalLink() {
            $page = new HtmlPage();
            $page->addLink("./somewhere.php", "Label", true);
            $this->assertEqual($page->getAbsoluteLinks(), array(), "abs->%s");
            $this->assertIdentical($page->getRelativeLinks(), array("./somewhere.php"), "rel->%s");
            $this->assertEqual($page->getUrls("Label"), array("./somewhere.php"));
        }
        function testAddInternalLink() {
            $page = new HtmlPage();
            $page->addLink("somewhere.php", "Label");
            $this->assertEqual($page->getAbsoluteLinks(), array(), "abs->%s");
            $this->assertIdentical($page->getRelativeLinks(), array("./somewhere.php"), "rel->%s");
            $this->assertEqual($page->getUrls("Label"), array("./somewhere.php"));
        }
    }
?>