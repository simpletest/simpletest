<?php
    // $Id$
    
    if (!defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', '../');
    }
    require_once(SIMPLE_TEST . 'browser.php');
    require_once(SIMPLE_TEST . 'http.php');
    Mock::generate('SimpleHttpRequest');
    Mock::generate('SimpleHttpResponse');
    Mock::generate('SimpleHttpHeaders');
    Mock::generate('SimplePage');
    Mock::generatePartial('SimpleBrowser', 'MockRequestSimpleBrowser', array('_createRequest'));
    Mock::generatePartial('SimpleBrowser', 'MockFetchSimpleBrowser', array('fetchResponse'));
    Mock::generatePartial('SimpleBrowser', 'MockParseSimpleBrowser', array('fetchResponse', '_parse'));
    
    class TestOfBrowserCookies extends UnitTestCase {
        function TestOfBrowserCookies() {
            $this->UnitTestCase();
        }
        function &_createStandardResponse() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue("getNewCookies", array());
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue("isError", false);
            $response->setReturnValue("getContent", "stuff");
            $response->setReturnReference("getHeaders", $headers);
            return $response;
        }
        function &_createCookieSite($cookies) {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue("getNewCookies", $cookies);
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue("isError", false);
            $response->setReturnReference("getHeaders", $headers);
            $response->setReturnValue("getContent", "stuff");
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference("fetch", $response);
            return $request;
        }
        function &_createPartialBrowser(&$request) {
            $browser = &new MockRequestSimpleBrowser($this);
            $browser->setReturnReference('_createRequest', $request);
            $browser->SimpleBrowser();
            return $browser;
        }
        function testSend() {
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference("fetch", $this->_createStandardResponse());
            $request->expectArguments("setCookie", array(new SimpleCookie("a", "A")));
            $request->expectCallCount("setCookie", 1);
            
            $browser = &$this->_createPartialBrowser($request);
            $browser->setCookie("a", "A");
            $response = $browser->fetchResponse(
                    "GET",
                    new SimpleUrl("http://this.com/this/path/page.html"),
                    array());
            $this->assertEqual($response->getContent(), "stuff");
            $request->tally();
        }
        function testReceiveExisting() {
            $request = &$this->_createCookieSite(array(new SimpleCookie("a", "AAAA", "this/path/")));
            $browser = &$this->_createPartialBrowser($request);
            $browser->setCookie("a", "A");
            $browser->fetchResponse(
                    "GET",
                    new SimpleUrl("http://this.host/this/path/page.html"),
                    array());
            $this->assertEqual($browser->getCookieValue("this.com", "this/path/", "a"), "AAAA");
        }
        function testClear() {
            $request = &$this->_createCookieSite(array(
                    new SimpleCookie("a", "b", "this/path/", "Wed, 25-Dec-02 04:24:19 GMT")));
            $browser = &$this->_createPartialBrowser($request);
            $browser->fetchResponse(
                    "GET",
                    new SimpleUrl("http://this.host/this/path/page.html"),
                    array());
            $browser->setCookie("a", "A", "this/path/", "Wed, 25-Dec-02 04:24:21 GMT");
            $this->assertEqual(
                    $browser->get("http://this.com/this/path/page.html", false),
                    "stuff");
            $this->assertIdentical(
                    $browser->getCookieValue("this.com", "this/path/", "a"),
                    "b");
            $browser->restartSession("Wed, 25-Dec-02 04:24:20 GMT");
            $this->assertIdentical(
                    $browser->getCookieValue("this.com", "this/path/", "a"),
                    false);
        }
        function testAgeingAndClearing() {
            $request = &$this->_createCookieSite(array(
                    new SimpleCookie("a", "A", "this/path/", "Wed, 25-Dec-02 04:24:21 GMT")));
            $browser = &$this->_createPartialBrowser($request);
            $browser->fetchResponse(
                    "GET",
                    new SimpleUrl("http://this.host/this/path/page.html"),
                    array());
            $browser->restartSession("Wed, 25-Dec-02 04:24:20 GMT");
            $this->assertIdentical(
                    $browser->getCookieValue("this.com", "this/path/", "a"),
                    "A");
            $browser->ageCookies(2);
            $browser->restartSession("Wed, 25-Dec-02 04:24:20 GMT");
            $this->assertIdentical(
                    $browser->getCookieValue("this.com", "this/path/", "a"),
                    false);
        }
        function testReading() {
            $request = &$this->_createCookieSite(array(
                    new SimpleCookie("a", "AAA", "this/path/")));
            $browser = &$this->_createPartialBrowser($request);
            $this->assertNull($browser->getBaseCookieValue("a"));
            $browser->setCookie("b", "BBB", "this.com", "this/path/");
            $browser->get("http://this.com/this/path/page.html", false);
            $this->assertEqual($browser->getBaseCookieValue("a"), "AAA");
            $this->assertEqual($browser->getBaseCookieValue("b"), "BBB");
        }
    }
    
    class TestOfParsedPageAccess extends UnitTestCase {
        function TestOfParsedPageAccess() {
            $this->UnitTestCase();
        }
        function &loadPage(&$page) {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue('getMimeType', 'text/html');
            $headers->setReturnValue('getResponseCode', 200);
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', 'stuff');
            $response->setReturnReference('getHeaders', $headers);
            
            $browser = &new MockParseSimpleBrowser($this);
            $browser->setReturnReference('fetchResponse', $response);
            $browser->setReturnReference('_parse', $page);
            $browser->expectOnce('_parse', array('stuff'));
            $browser->SimpleBrowser();
            
            $browser->get('http://this.com/page.html');
            return $browser;
        }
        function testParse() {
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getRaw', 'Raw HTML');
            $page->setReturnValue('getTitle', 'Here');
            
            $browser = &$this->loadPage($page);

            $this->assertEqual($browser->getContent(), 'Raw HTML');
            $this->assertEqual($browser->getTitle(), 'Here');
            $browser->tally();
        }
        function testFormHandling() {
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getField', 'Value');
            $page->expectOnce('getField', array('key'));
            $page->expectOnce('setField', array('key', 'Value'));
            
            $browser = &$this->loadPage($page);
            $this->assertEqual($browser->getField('key'), 'Value');
            
            $browser->setField('key', 'Value');
            $page->tally();
        }
    }
?>