<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'runner.php');
    require_once(SIMPLE_TEST . 'browser.php');
    
    Mock::generate("TestBrowser");
    
    SimpleTestOptions::ignore("MockBrowserWebTestCase");

    class MockBrowserWebTestCase extends WebTestCase {
        function MockBrowserWebTestCase($label = false) {
            $this->WebTestCase($label);
        }
        function &createBrowser() {
            return new MockTestBrowser($this);
        }
    }
    
    class TestOfWebFetching extends MockBrowserWebTestCase {
        function TestOfWebFetching() {
            $this->MockBrowserWebTestCase();
        }
        function setUp() {
            $browser = &$this->getBrowser();
            $browser->setReturnValue("get", "Hello world");
            $browser->expectArguments("get", array("http://my-site.com/", false));
            $browser->expectCallCount("get", 1);
        }
        function tearDown() {
            $browser = &$this->getBrowser();
            $browser->_assertTrue(true, "Hello", $this);
            $browser->tally();
        }
        function testContentAccess() {
            $this->assertTrue(is_a($this->getBrowser(), "MockTestBrowser"));
            $this->get("http://my-site.com/");
        }
        function testRawPatternMatching() {
            $this->get("http://my-site.com/");
            $this->assertWantedPattern('/hello/i');
            $this->assertNoUnwantedPattern('/goodbye/i');
        }
        function testResponseCodes() {
            $browser = &$this->getBrowser();
            $browser->expectArguments("assertResponse", array(404, "%s"));
            $browser->expectCallCount("assertResponse", 1);
            $this->get("http://my-site.com/");
            $this->assertResponse(404);
        }
        function testMimeTypes() {
            $browser = &$this->getBrowser();
            $browser->expectArguments("assertMime", array("text/html", "%s"));
            $browser->expectCallCount("assertMime", 1);
            $this->get("http://my-site.com/");
            $this->assertMime("text/html");
        }
    }
    
    class TestOfWebPageLinkParsing extends MockBrowserWebTestCase {
        function TestOfWebPageLinkParsing() {
            $this->MockBrowserWebTestCase();
        }
        function setUp() {
            $browser = &$this->getBrowser();
            $browser->setReturnValueAt(
                    0,
                    "get",
                    "<a href=\"http://my-site.com/there\" id=\"2\">Me</a>, <a href=\"a\">Me</a>");
            $browser->setReturnValueAt(1, "get", "Found it");
            $browser->expectCallCount("get", 2);
        }
        function tearDown() {
            $browser = &$this->getBrowser();
            $browser->tally();
        }
        function testLinkClick() {
            $browser = &$this->getBrowser();
            $browser->expectArgumentsAt(1, "get", array("http://my-site.com/there", false));
            $this->get("http://my-site.com/link");
            $this->assertFalse($this->clickLink('You'));
            $this->assertTrue($this->clickLink('Me'));
            $this->assertFalse($this->clickLink('Me'));
            $this->assertWantedPattern('/Found it/i');
        }
        function testLinkIdClick() {
            $browser = &$this->getBrowser();
            $browser->expectArgumentsAt(1, "get", array("http://my-site.com/there", false));
            $this->get("http://my-site.com/link");
            $this->assertFalse($this->clickLinkId(0));
            $this->_getHtml();
            $this->assertTrue($this->clickLinkId(2));
            $this->assertWantedPattern('/Found it/i');
        }
        function testLinkIndexClick() {
            $browser = &$this->getBrowser();
            $browser->expectArgumentsAt(1, "get", array("a", false));
            $this->get("http://my-site.com/link");
            $this->assertFalse($this->clickLink('Me', 2));
            $this->assertTrue($this->clickLink('Me', 1));
        }
    }
    
    class TestOfWebPageTitleParsing extends MockBrowserWebTestCase {
        function TestOfWebPageTitleParsing() {
            $this->MockBrowserWebTestCase();
        }
        function testTitle() {
            $browser = &$this->getBrowser();
            $browser->setReturnValue(
                    "get",
                    "<html><head><title>Pretty page</title></head></html>");
            $this->get("http://my-site.com/");
            $this->assertTitle("Pretty page");
            $browser->tally();
        }
    }
?>