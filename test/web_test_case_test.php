<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'observer.php');
    require_once(SIMPLE_TEST . 'browser.php');
    
    Mock::generate("TestObserver");
    Mock::generate("TestBrowser");
    
    GroupTest::ignore("MockBrowserWebTestCase");

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
            $browser->setReturnValue("fetchContent", "Hello world");
            $browser->expectArguments("fetchContent", array("http://my-site.com/"));
            $browser->expectCallCount("fetchContent", 1);
        }
        function tearDown() {
            $browser = &$this->getBrowser();
            $browser->_assertTrue(true, "Hello", $this);
            $browser->tally();
        }
        function testContentAccess() {
            $this->assertTrue(is_a($this->getBrowser(), "MockTestBrowser"));
            $this->fetch("http://my-site.com/");
        }
        function testRawPatternMatching() {
            $this->fetch("http://my-site.com/");
            $this->assertWantedPattern('/hello/i');
            $this->assertNoUnwantedPattern('/goodbye/i');
        }
    }
    
    class TestOfWebPageParsing extends MockBrowserWebTestCase {
        function TestOfWebPageParsing() {
            $this->MockBrowserWebTestCase();
        }
        function setUp() {
            $browser = &$this->getBrowser();
            $browser->setReturnValueAt(
                    0,
                    "fetchContent",
                    "<a href=\"http://my-site.com/there\" id=\"2\">Me</a>, <a href=\"a\">Me</a>");
            $browser->setReturnValueAt(1, "fetchContent", "Found it");
            $browser->expectCallCount("fetchContent", 2);
        }
        function tearDown() {
            $browser = &$this->getBrowser();
            $browser->tally();
        }
        function testLinkClick() {
            $browser = &$this->getBrowser();
            $browser->expectArgumentsAt(1, "fetchContent", array("http://my-site.com/there"));
            $this->fetch("http://my-site.com/link");
            $this->assertFalse($this->clickLink('You'));
            $this->assertTrue($this->clickLink('Me'));
            $this->assertFalse($this->clickLink('Me'));
            $this->assertWantedPattern('/Found it/i');
        }
        function testLinkIdClick() {
            $browser = &$this->getBrowser();
            $browser->expectArgumentsAt(1, "fetchContent", array("http://my-site.com/there"));
            $this->fetch("http://my-site.com/link");
            $this->assertFalse($this->clickLinkId(0));
            $this->_getHtml();
            $this->assertTrue($this->clickLinkId(2));
            $this->assertWantedPattern('/Found it/i');
        }
        function testLinkIndexClick() {
            $browser = &$this->getBrowser();
            $browser->expectArgumentsAt(1, "fetchContent", array("./a"));
            $this->fetch("http://my-site.com/link");
            $this->assertFalse($this->clickLink('Me', 2));
            $this->assertTrue($this->clickLink('Me', 1));
        }
    }
?>