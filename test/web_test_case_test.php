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
?>