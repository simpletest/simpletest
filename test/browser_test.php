<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'browser.php');
    require_once(SIMPLE_TEST . 'http.php');
    Mock::generate("UnitTestCase");
    Mock::generate("SimpleHttpRequest");
    Mock::generate("SimpleHttpResponse");

    class TestOfCookieJar extends UnitTestCase {
        function TestOfCookieJar() {
            $this->UnitTestCase();
        }
        function testAddCookie() {
            $jar = new CookieJar();
            $jar->setCookie(new SimpleCookie("a", "A"));
            $cookies = $jar->getValidCookies();
            $this->assertEqual(count($cookies), 1);
            $this->assertEqual($cookies[0]->getValue(), "A");
        }
        function testHostFilter() {
            $jar = new CookieJar();
            $cookie = new SimpleCookie("a", "A");
            $cookie->setHost("my-host.com");
            $jar->setCookie($cookie);
            $cookie = new SimpleCookie("b", "B");
            $cookie->setHost("another-host.com");
            $jar->setCookie($cookie);
            $cookie = new SimpleCookie("c", "C");
            $jar->setCookie($cookie);
            $cookies = $jar->getValidCookies("my-host.com");
            $this->assertEqual(count($cookies), 2);
            $this->assertEqual($cookies[0]->getValue(), "A");
            $this->assertEqual($cookies[1]->getValue(), "C");
            $this->assertEqual(count($jar->getValidCookies("another-host.com")), 2);
            $this->assertEqual(count($jar->getValidCookies("www.another-host.com")), 2);
            $this->assertEqual(count($jar->getValidCookies("new-host.org")), 1);
            $this->assertEqual(count($jar->getValidCookies()), 3);
        }
        function testPathFilter() {
            $jar = new CookieJar();
            $jar->setCookie(new SimpleCookie("a", "A", "/path/"));
            $this->assertEqual(count($jar->getValidCookies(false, "/")), 0);
            $this->assertEqual(count($jar->getValidCookies(false, "/elsewhere")), 0);
            $this->assertEqual(count($jar->getValidCookies(false, "/path/")), 1);
            $this->assertEqual(count($jar->getValidCookies(false, "/path")), 1);
            $this->assertEqual(count($jar->getValidCookies(false, "/pa")), 0);
            $this->assertEqual(count($jar->getValidCookies(false, "/path/here/")), 1);
        }
        function testPathFilterDeeply() {
            $jar = new CookieJar();
            $jar->setCookie(new SimpleCookie("a", "A", "/path/more_path/"));
            $this->assertEqual(count($jar->getValidCookies(false, "/path/")), 0);
            $this->assertEqual(count($jar->getValidCookies(false, "/path")), 0);
            $this->assertEqual(count($jar->getValidCookies(false, "/pa")), 0);
            $this->assertEqual(count($jar->getValidCookies(false, "/path/more_path/")), 1);
            $this->assertEqual(count($jar->getValidCookies(false, "/path/more_path/and_more")), 1);
            $this->assertEqual(count($jar->getValidCookies(false, "/path/not_here/")), 0);
        }
        function testMultipleCookieWithDifferentPaths() {
            $jar = new CookieJar();
            $jar->setCookie(new SimpleCookie("a", "abc", "/"));
            $jar->setCookie(new SimpleCookie("a", "123", "/path/here/"));
            $cookies = $jar->getValidCookies("my-host.com", "/");
            $this->assertEqual($cookies[0]->getPath(), "/");
            $cookies = $jar->getValidCookies("my-host.com", "/path/");
            $this->assertEqual($cookies[0]->getPath(), "/");
            $cookies = $jar->getValidCookies("my-host.com", "/path/here");
            $this->assertEqual($cookies[0]->getPath(), "/");
            $this->assertEqual($cookies[1]->getPath(), "/path/here/");
            $cookies = $jar->getValidCookies("my-host.com", "/path/here/there");
            $this->assertEqual($cookies[0]->getPath(), "/");
            $this->assertEqual($cookies[1]->getPath(), "/path/here/");
        }
        function testOverwrite() {
            $jar = new CookieJar();
            $jar->setCookie(new SimpleCookie("a", "abc", "/"));
            $jar->setCookie(new SimpleCookie("a", "cde", "/"));
            $cookies = $jar->getValidCookies();
            $this->assertIdentical($cookies[0]->getValue(), "cde");
        }
        function testClearSessionCookies() {
            $jar = new CookieJar();
            $jar->setCookie(new SimpleCookie("a", "A", "/"));
            $jar->restartSession();
            $this->assertEqual(count($jar->getValidCookies(false, "/")), 0);
        }
        function testExpiryFilter() {
            $jar = new CookieJar();
            $jar->setCookie(new SimpleCookie("a", "A", "/", "Wed, 25-Dec-02 04:24:20 GMT"));
            $jar->restartSession("Wed, 25-Dec-02 04:24:19 GMT");
            $this->assertEqual(count($jar->getValidCookies(false, "/")), 1);
            $jar->restartSession("Wed, 25-Dec-02 04:24:21 GMT");
            $this->assertEqual(count($jar->getValidCookies(false, "/")), 0);
        }
        function testCookieClearing() {
            $jar = new CookieJar();
            $jar->setCookie(new SimpleCookie("a", "abc", "/"));
            $jar->setCookie(new SimpleCookie("a", "", "/"));
            $this->assertEqual(count($cookies = $jar->getValidCookies(false, "/")), 1);
            $this->assertIdentical($cookies[0]->getValue(), "");
        }
        function testCookieClearByDate() {
            $jar = new CookieJar();
            $jar->setCookie(new SimpleCookie("a", "abc", "/", "Wed, 25-Dec-02 04:24:21 GMT"));
            $jar->setCookie(new SimpleCookie("a", "def", "/", "Wed, 25-Dec-02 04:24:19 GMT"));
            $cookies = $jar->getValidCookies(false, "/");
            $this->assertIdentical($cookies[0]->getValue(), "def");
            $jar->restartSession("Wed, 25-Dec-02 04:24:20 GMT");
            $this->assertEqual(count($jar->getValidCookies(false, "/")), 0);
        }
    }

    class TestOfBrowser extends UnitTestCase {
        function TestOfBrowser() {
            $this->UnitTestCase();
        }
        function testAssertionChaining() {
            $test = &new MockUnitTestCase($this);
            $test->expectArgumentsAt(0, "assertTrue", array(true, "Good"));
            $test->expectArgumentsAt(1, "assertTrue", array(false, "Bad"));
            $test->expectCallCount("assertTrue", 2);
            $browser = &new TestBrowser($test);
            $browser->_assertTrue(true, "Good");
            $browser->_assertTrue(false, "Bad");
            $test->tally();
        }
    }

    class TestOfBadHosts extends UnitTestCase {
        function TestOfBadHosts() {
            $this->UnitTestCase();
        }
        function &_createSimulatedBadHost() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue("isError", true);
            $response->setReturnValue("getError", "Bad socket");
            $response->setReturnValue("getNewCookies", array());
            $response->setReturnValue("getContent", false);
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference("fetch", $response);
            return $request;
        }
        function testUntestedHost() {
            $test = &new MockUnitTestCase($this);
            $test->expectCallCount("assertTrue", 0);
            $browser = &new TestBrowser($test);
            $request = &$this->_createSimulatedBadHost();
            $this->assertIdentical(
                    $browser->fetchUrl("http://this.host/this/path/page.html", &$request),
                    false);
            $test->tally();
        }
        function testFailingBadHost() {
            $test = &new MockUnitTestCase($this);
            $test->expectArgumentsAt(0, "assertTrue", array(false, '*'));
            $test->expectCallCount("assertTrue", 1);
            $browser = &new TestBrowser($test);
            $browser->expectConnection();
            $request = &$this->_createSimulatedBadHost();
            $this->assertIdentical(
                    $browser->fetchUrl("http://this.host/this/path/page.html", &$request),
                    false);
            $test->tally();
        }
        function testExpectingBadHost() {
            $test = &new MockUnitTestCase($this);
            $test->expectArgumentsAt(0, "assertTrue", array(true, '*'));
            $test->expectCallCount("assertTrue", 1);
            $browser = &new TestBrowser($test);
            $browser->expectConnection(false);
            $request = &$this->_createSimulatedBadHost();
            $this->assertIdentical(
                    $browser->fetchUrl("http://this.host/this/path/page.html", &$request),
                    false);
            $test->tally();
        }
    }

    class TestOfHeaderExpectations extends UnitTestCase {
        function TestOfHeaderExpectations() {
            $this->UnitTestCase();
        }
        function setUp() {
            $this->_response = &new MockSimpleHttpResponse($this);
            $this->_response->setReturnValue("isError", false);
            $this->_response->setReturnValue("getNewCookies", array());
            $this->_response->setReturnValue("getContent", false);
            $this->_request = &new MockSimpleHttpRequest($this);
            $this->_request->setReturnReference("fetch", $this->_response);
            $this->_test = &new MockUnitTestCase($this);
        }
        function testExpectedResponseCodes() {
            $this->_response->setReturnValue("getResponseCode", 404);
            $this->_test->expectArguments("assertTrue", array(true, "*"));
            $this->_test->expectCallCount("assertTrue", 1);
            $browser = &new TestBrowser($this->_test);
            $browser->expectResponseCodes(array(404));
            $browser->fetchUrl("http://this.host/this/path/page.html", &$this->_request);
            $this->_test->tally();
        }
        function testUnwantedResponseCode() {
            $this->_response->setReturnValue("getResponseCode", 404);
            $this->_test->expectArguments("assertTrue", array(false, "*"));
            $this->_test->expectCallCount("assertTrue", 1);
            $browser = &new TestBrowser($this->_test);
            $browser->expectResponseCodes(array(100, 200));
            $browser->fetchUrl("http://this.host/this/path/page.html", &$this->_request);
            $this->_test->tally();
        }
        function testExpectedMimeTypes() {
            $this->_response->setReturnValue("getMimeType", "text/xml");
            $this->_test->expectArguments("assertTrue", array(true, "*"));
            $this->_test->expectCallCount("assertTrue", 1);
            $browser = &new TestBrowser($this->_test);
            $browser->expectMimeTypes(array("text/plain", "text/xml"));
            $browser->fetchUrl("http://this.host/this/path/page.xml", &$this->_request);
            $this->_test->tally();
        }
        function testClearExpectations() {
            $this->_response->setReturnValue("getResponseCode", 404);
            $this->_test->expectCallCount("assertTrue", 0);
            $browser = &new TestBrowser($this->_test);
            $browser->expectResponseCodes(array(100, 200));
            $browser->expectConnection();
            $browser->_clearExpectations();
            $browser->fetchUrl("http://this.host/this/path/page.html", &$this->_request);
            $this->_test->tally();
        }
    }
    
    class testOfBrowserCookies extends UnitTestCase {
        function TestOfBrowserCookies() {
            $this->UnitTestCase();
        }
        function &_createStandardResponse() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue("isError", false);
            $response->setReturnValue("getNewCookies", array());
            $response->setReturnValue("getContent", "stuff");
            return $response;
        }
        function &_createCookieSite($cookies) {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue("isError", false);
            $response->setReturnValue("getContent", "stuff");
            $response->setReturnValue("getNewCookies", $cookies);
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference("fetch", $response);
            return $request;
        }
        function testSendCookie() {
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference("fetch", $this->_createStandardResponse());
            $request->expectArguments("setCookie", array(new SimpleCookie("a", "A")));
            $request->expectCallCount("setCookie", 1);
            $browser = &new TestBrowser(new MockUnitTestCase($this));
            $browser->setCookie("a", "A");
            $browser->fetchUrl("http://this.host/this/path/page.html", &$request);
            $request->tally();
        }
        function testMissingCookie() {
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference("fetch", $this->_createStandardResponse());
            $test = &new MockUnitTestCase($this);
            $test->expectArguments("assertTrue", array(false, "*"));
            $test->expectCallCount("assertTrue", 1);
            $browser = &new TestBrowser($test);
            $browser->expectCookie("a", "A");
            $browser->fetchUrl("http://this.host/this/path/page.html", &$request);
            $test->tally();
            $this->assertIdentical($browser->getCookieValues("this.host", "this/page/", "a"), array());
        }
        function testNewCookie() {
            $request = &$this->_createCookieSite(array(new SimpleCookie("a", "A", "this/path/")));
            $test = &new MockUnitTestCase($this);
            $test->expectArguments("assertTrue", array(true, "*"));
            $test->expectCallCount("assertTrue", 1);
            $browser = &new TestBrowser($test);
            $browser->expectCookie("a", "A");
            $browser->fetchUrl("http://this-host.com/this/path/page.html", &$request);
            $test->tally();
            $this->assertEqual($browser->getCookieValues("this-host.com", "this/path/", "a"), array("A"));
            $this->assertIdentical($browser->getCookieValues("this-host.com", "this/", "a"), array());
            $this->assertIdentical($browser->getCookieValues("another.com", "this/path/", "a"), array());
        }
        function testReceiveExistingCookie() {
            $request = &$this->_createCookieSite(array(new SimpleCookie("a", "AAAA", "this/path/")));
            $browser = &new TestBrowser(new MockUnitTestCase($this));
            $browser->setCookie("a", "A");
            $browser->fetchUrl("http://this.host/this/path/page.html", &$request);
            $this->assertEqual($browser->getCookieValues("this.host", "this/path/", "a"), array("AAAA"));
        }
        function testClearCookie() {
            $request = &$this->_createCookieSite(array(new SimpleCookie(
                    "a",
                    "",
                    "this/path/",
                    "Wed, 25-Dec-02 04:24:19 GMT")));
            $browser = &new TestBrowser(new MockUnitTestCase($this));
            $browser->setCookie("a", "A", "this/path/", "Wed, 25-Dec-02 04:24:21 GMT");
            $browser->fetchUrl("http://this.host/this/path/page.html", &$request);
            $this->assertIdentical(
                    $browser->getCookieValues("this.host", "this/path/", "a"),
                    array(""));
            $browser->restartSession("Wed, 25-Dec-02 04:24:20 GMT");
            $this->assertIdentical(
                    $browser->getCookieValues("this.host", "this/path/", "a"),
                    array());
        }
    }
?>