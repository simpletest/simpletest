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
    Mock::generate("SimpleHttpHeaders");
    Mock::generatePartial('SimpleBrowser', 'MockRequestSimpleBrowser', array('_createRequest'));
    Mock::generatePartial('SimpleBrowser', 'MockFetchSimpleBrowser', array('fetchResponse'));
    Mock::generatePartial('TestBrowser', 'MockRequestTestBrowser', array('_createRequest'));

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
        function testExpiryFilterByDate() {
            $cookie = new SimpleCookie("a", "A", "/", "Wed, 25-Dec-02 04:24:20 GMT");
            $jar = new CookieJar();
            $jar->setCookie($cookie);
            $jar->restartSession("Wed, 25-Dec-02 04:24:19 GMT");
            $this->assertIdentical($list = $jar->getValidCookies(false, "/"), array($cookie));
            $jar->restartSession("Wed, 25-Dec-02 04:24:21 GMT");
            $this->assertIdentical($list = $jar->getValidCookies(false, "/"), array());
        }
        function testExpiryFilterByAgeing() {
            $cookie = new SimpleCookie("a", "A", "/", "Wed, 25-Dec-02 04:24:20 GMT");
            $jar = new CookieJar();
            $jar->setCookie($cookie);
            $jar->restartSession("Wed, 25-Dec-02 04:24:19 GMT");
            $this->assertIdentical($list = $jar->getValidCookies(false, "/"), array($cookie));
            $jar->agePrematurely(2);
            $jar->restartSession("Wed, 25-Dec-02 04:24:19 GMT");
            $this->assertIdentical($list = $jar->getValidCookies(false, "/"), array());
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
    
    class TestOfExpandomaticUrl extends UnitTestCase {
        function TestOfExpandomaticUrl() {
            $this->UnitTestCase();
        }
        function testSetBase() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue("getNewCookies", array());
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue("isError", false);
            $response->setReturnReference("getHeaders", $headers);
            
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference("fetch", $response);
            
            $browser = &new MockRequestSimpleBrowser($this);
            $browser->setReturnReference('_createRequest', $request);
            $browser->SimpleBrowser();
            
            $browser->get("http://this.com/this/path/page.html", false);
            $this->assertEqual(
                    $browser->getBaseUrl(),
                    "http://this.com/this/path/");
        }
        function testSetCurrent() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue("getNewCookies", array());
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue("isError", false);
            $response->setReturnReference("getHeaders", $headers);
            
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference("fetch", $response);
            
            $browser = &new MockRequestSimpleBrowser($this);
            $browser->setReturnReference('_createRequest', $request);
            $browser->SimpleBrowser();
            
            $browser->get("http://this.com/this/path/page.html", false);
            $this->assertEqual(
                    $browser->getCurrentUrl(),
                    "http://this.com/this/path/page.html");
        }
        function testSetCurrentWithPost() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue("getNewCookies", array());
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue("isError", false);
            $response->setReturnReference("getHeaders", $headers);
            
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference("fetch", $response);
            
            $browser = &new MockRequestSimpleBrowser($this);
            $browser->setReturnReference('_createRequest', $request);
            $browser->SimpleBrowser();
            
            $browser->post("http://this.com/this/path/page.html", false);
            $this->assertEqual(
                    $browser->getCurrentUrl(),
                    "http://this.com/this/path/page.html");
        }
    }
    
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
    
    class TestOfFetchingMethods extends UnitTestCase {
        function TestOfFetchingMethods() {
            $this->UnitTestCase();
        }
        function testGet() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue('getMimeType', 'text/html');
            $headers->setReturnValue('getResponseCode', 200);
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue("getContent", "stuff");
            $response->setReturnReference("getHeaders", $headers);
            
            $url = new SimpleUrl("http://this.com/page.html");
            $url->addRequestParameters(array("a" => "A", "b" => "B"));
            
            $browser = &new MockFetchSimpleBrowser($this);
            $browser->setReturnReference("fetchResponse", $response);
            $browser->expectArguments(
                    "fetchResponse",
                    array("GET", $url, array("a" => "A", "b" => "B")));
            $browser->SimpleBrowser();
            
            $this->assertIdentical(
                    $browser->get("http://this.com/page.html", array("a" => "A", "b" => "B")),
                    "stuff");
            $browser->_setResponse($response);
            $this->assertEqual($browser->getMimeType(), 'text/html');
            $this->assertequal($browser->getResponseCode(), 200);
            $browser->tally();
        }
        function testHead() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue("getContent", "stuff");
            
            $url = new SimpleUrl("http://this.com/page.html");
            $url->addRequestParameters(array("a" => "A", "b" => "B"));
            
            $browser = &new MockFetchSimpleBrowser($this);
            $browser->setReturnReference("fetchResponse", $response);
            $browser->expectArguments(
                    "fetchResponse",
                    array("HEAD", $url, array("a" => "A", "b" => "B")));
            $browser->expectCallCount("fetchResponse", 1);
            $browser->SimpleBrowser();
            
            $this->assertIdentical(
                    $browser->head("http://this.com/page.html", array("a" => "A", "b" => "B")),
                    true);
            $browser->tally();
        }
        function testPost() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue("getContent", "stuff");
            
            $expected_request = new SimpleHttpPushRequest(new SimpleUrl("http://this.com/page.html"), "a=A&b=B");
            $expected_request->addHeaderLine('Content-Type: application/x-www-form-urlencoded');
            
            $browser = &new MockFetchSimpleBrowser($this);
            $browser->setReturnReference("fetchResponse", $response);
            $browser->expectCallCount("fetchResponse", 1);
            $browser->expectArguments(
                    "fetchResponse",
                    array("POST", new SimpleUrl("http://this.com/page.html"), array("a" => "A", "b" => "B")));
            $browser->SimpleBrowser();
            
            $this->assertIdentical(
                    $browser->post("http://this.com/page.html", array("a" => "A", "b" => "B")),
                    "stuff");
            $browser->tally();
        }
    }
    
    class TestOfBrowserRedirects extends UnitTestCase {
        function TestOfBrowserRedirects() {
            $this->UnitTestCase();
        }
        function &createRedirect($content, $redirect) {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue("getNewCookies", array());
            $headers->setReturnValue("isRedirect", (boolean)$redirect);
            $headers->setReturnValue("getLocation", $redirect);
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue("getContent", $content);
            $response->setReturnReference("getHeaders", $headers);
            
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference("fetch", $response);
            return $request;
        }
        function testDisabledRedirects() {
            $browser = &new MockRequestSimpleBrowser($this);
            $browser->setReturnReference(
                    "_createRequest",
                    $this->createRedirect("stuff", "there.html"));
            $browser->expectOnce("_createRequest");
            $browser->SimpleBrowser();
            $browser->setMaximumRedirects(0);
            $this->assertEqual($browser->get("here.html"), "stuff");
            $browser->tally();
        }
        function testSingleRedirect() {
            $browser = &new MockRequestSimpleBrowser($this);
            $browser->setReturnReferenceAt(
                    0,
                    "_createRequest",
                    $this->createRedirect("first", "two.html"));
            $browser->setReturnReferenceAt(
                    1,
                    "_createRequest",
                    $this->createRedirect("second", "three.html"));
            $browser->expectCallCount("_createRequest", 2);
            $browser->SimpleBrowser();
            $browser->setMaximumRedirects(1);
            $this->assertEqual($browser->get("one.html"), "second");
            $browser->tally();
        }
        function testDoubleRedirect() {
            $browser = &new MockRequestSimpleBrowser($this);
            $browser->setReturnReferenceAt(
                    0,
                    "_createRequest",
                    $this->createRedirect("first", "two.html"));
            $browser->setReturnReferenceAt(
                    1,
                    "_createRequest",
                    $this->createRedirect("second", "three.html"));
            $browser->setReturnReferenceAt(
                    2,
                    "_createRequest",
                    $this->createRedirect("third", "four.html"));
            $browser->expectCallCount("_createRequest", 3);
            $browser->SimpleBrowser();
            $browser->setMaximumRedirects(2);
            $this->assertEqual($browser->get("one.html"), "third");
            $browser->tally();
        }
        function testSuccessAfterRedirect() {
            $browser = &new MockRequestSimpleBrowser($this);
            $browser->setReturnReferenceAt(
                    0,
                    "_createRequest",
                    $this->createRedirect("first", "two.html"));
            $browser->setReturnReferenceAt(
                    1,
                    "_createRequest",
                    $this->createRedirect("second", false));
            $browser->setReturnReferenceAt(
                    2,
                    "_createRequest",
                    $this->createRedirect("third", "four.html"));
            $browser->expectCallCount("_createRequest", 2);
            $browser->SimpleBrowser();
            $browser->setMaximumRedirects(2);
            $this->assertEqual($browser->get("one.html"), "second");
            $browser->tally();
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
            $response->setReturnValue("getContent", false);
            
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference("fetch", $response);
            return $request;
        }
        function testUntestedHost() {
            $request = &$this->_createSimulatedBadHost();
            
            $browser = &new MockRequestSimpleBrowser($this);
            $browser->setReturnReference('_createRequest', $request);
            $browser->SimpleBrowser();
            
            $this->assertIdentical($browser->getResponseCode(), false);
            $this->assertIdentical($browser->getMimeType(), false);
            $this->assertIdentical(
                    $browser->get("http://this.host/this/path/page.html", false),
                    false);
        }
    }
?>