<?php
    // $Id$
    
    if (!defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', '../');
    }
    require_once(SIMPLE_TEST . 'user_agent.php');
    require_once(SIMPLE_TEST . 'http.php');
    Mock::generate('SimpleHttpRequest');
    Mock::generate('SimpleHttpResponse');
    Mock::generate('SimpleHttpHeaders');
    Mock::generatePartial('SimpleUserAgent', 'MockRequestUserAgent', array('_createRequest'));

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
        function testFetchSetsLastUrl() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue("getNewCookies", array());
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue("isError", false);
            $response->setReturnReference("getHeaders", $headers);
            
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference("fetch", $response);
            
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReference('_createRequest', $request);
            $agent->SimpleUserAgent();
            
            $agent->fetchResponse(
                    'GET',
                    'http://this.com/this/path/page.html',
                    false);
            $this->assertEqual(
                    $agent->getCurrentUrl(),
                    "http://this.com/this/path/page.html");
            $this->assertEqual(
                    $agent->getBaseUrl(),
                    'http://this.com/this/path/');
        }
        function testSetLastUrlWithPost() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue('getNewCookies', array());
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('isError', false);
            $response->setReturnReference('getHeaders', $headers);
            
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference('fetch', $response);
            
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReference('_createRequest', $request);
            $agent->SimpleUserAgent();
            
            $agent->fetchResponse(
                    'POST',
                    'http://this.com/this/path/page.html',
                    false);
            $this->assertEqual(
                    $agent->getCurrentUrl(),
                    'http://this.com/this/path/page.html');
            $this->assertEqual(
                    $agent->getBaseUrl(),
                    'http://this.com/this/path/');
        }
    }
    
    class TestOfFetchingUrlParameters extends UnitTestCase {
        function TestOfFetchingUrlParameters() {
            $this->UnitTestCase();
        }
        function testGet() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue('getMimeType', 'text/html');
            $headers->setReturnValue('getResponseCode', 200);
            $headers->setReturnValue('getNewCookies', array());
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', 'stuff');
            $response->setReturnReference('getHeaders', $headers);
            
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference('fetch', $response);
            
            $url = new SimpleUrl('http://this.com/page.html');
            $url->addRequestParameters(array("a" => "A", "b" => "B"));
            
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReference('_createRequest', $request);
            $agent->expectOnce(
                    '_createRequest',
                    array('GET', $url, array("a" => "A", "b" => "B")));
            $agent->SimpleUserAgent();
            
            $agent->fetchResponse(
                    'GET',
                    'http://this.com/page.html',
                    array("a" => "A", "b" => "B"));
            $agent->tally();
        }
        function _testHead() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue('getMimeType', 'text/html');
            $headers->setReturnValue('getResponseCode', 200);
            $headers->setReturnValue('getNewCookies', array());
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue("getContent", "stuff");
            
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference('fetch', $response);
            
            $url = new SimpleUrl("http://this.com/page.html");
            $url->addRequestParameters(array("a" => "A", "b" => "B"));
            
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReference('_createRequest', $request);
            $agent->expectOnce(
                    '_createRequest',
                    array('HEAD', $url, array("a" => "A", "b" => "B")));
            $agent->SimpleUserAgent();
            
            $agent->fetchResponse(
                    'HEAD',
                    'http://this.com/page.html',
                    array("a" => "A", "b" => "B"));
            $agent->tally();
        }
        function _testPost() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue('getMimeType', 'text/html');
            $headers->setReturnValue('getResponseCode', 200);
            $headers->setReturnValue('getNewCookies', array());
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue("getContent", "stuff");
            
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference('fetch', $response);
            
            $expected_request = new SimpleHttpPushRequest(new SimpleUrl("http://this.com/page.html"), "a=A&b=B");
            $expected_request->addHeaderLine('Content-Type: application/x-www-form-urlencoded');
            
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReference('_createRequest', $request);
            $agent->expectOnce(
                    '_createRequest',
                    array("POST", new SimpleUrl("http://this.com/page.html"), array("a" => "A", "b" => "B")));
            $agent->SimpleUserAgent();
            
            $agent->fetchResponse(
                    'POST',
                    'http://this.com/page.html',
                    array("a" => "A", "b" => "B"));
            $agent->tally();
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
        function &_createPartialFetcher(&$request) {
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReference('_createRequest', $request);
            $agent->SimpleUserAgent();
            return $agent;
        }
        function testSendingExistingCookie() {
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference("fetch", $this->_createStandardResponse());
            $request->expectArguments("setCookie", array(new SimpleCookie("a", "A")));
            $request->expectCallCount("setCookie", 1);
            
            $agent = &$this->_createPartialFetcher($request);
            $agent->setCookie("a", "A");
            $response = $agent->fetchResponse(
                    "GET",
                    "http://this.com/this/path/page.html",
                    array());
            $this->assertEqual($response->getContent(), "stuff");
            $request->tally();
        }
        function testOverwriteCookieThatAlreadyExists() {
            $request = &$this->_createCookieSite(array(new SimpleCookie("a", "AAAA", "this/path/")));
            $agent = &$this->_createPartialFetcher($request);
            
            $agent->setCookie("a", "A");
            $agent->fetchResponse(
                    "GET",
                    "http://this.com/this/path/page.html",
                    array());
            $this->assertEqual($agent->getCookieValue("this.com", "this/path/", "a"), "AAAA");
        }
        function testClearCookieBySettingExpiry() {
            $request = &$this->_createCookieSite(array(
                    new SimpleCookie("a", "b", "this/path/", "Wed, 25-Dec-02 04:24:19 GMT")));
            $agent = &$this->_createPartialFetcher($request);
            
            $agent->setCookie("a", "A", "this/path/", "Wed, 25-Dec-02 04:24:21 GMT");
            $agent->fetchResponse(
                    "GET",
                    "http://this.com/this/path/page.html",
                    array());
            $this->assertIdentical(
                    $agent->getCookieValue("this.com", "this/path/", "a"),
                    "b");
            $agent->restartSession("Wed, 25-Dec-02 04:24:20 GMT");
            $this->assertIdentical(
                    $agent->getCookieValue("this.com", "this/path/", "a"),
                    false);
        }
        function testAgeingAndClearing() {
            $request = &$this->_createCookieSite(array(
                    new SimpleCookie("a", "A", "this/path/", "Wed, 25-Dec-02 04:24:21 GMT")));
            $agent = &$this->_createPartialFetcher($request);
            
            $agent->fetchResponse(
                    "GET",
                    "http://this.com/this/path/page.html",
                    array());
            $agent->restartSession("Wed, 25-Dec-02 04:24:20 GMT");
            $this->assertIdentical(
                    $agent->getCookieValue("this.com", "this/path/", "a"),
                    "A");
            $agent->ageCookies(2);
            $agent->restartSession("Wed, 25-Dec-02 04:24:20 GMT");
            $this->assertIdentical(
                    $agent->getCookieValue("this.com", "this/path/", "a"),
                    false);
        }
        function testReadingIncomingAndSetCookies() {
            $request = &$this->_createCookieSite(array(
                    new SimpleCookie("a", "AAA", "this/path/")));
            $agent = &$this->_createPartialFetcher($request);
            
            $this->assertNull($agent->getBaseCookieValue("a"));
            $agent->fetchResponse(
                    "GET",
                    "http://this.com/this/path/page.html",
                    array());
            $agent->setCookie("b", "BBB", "this.com", "this/path/");
            $this->assertEqual($agent->getBaseCookieValue("a"), "AAA");
            $this->assertEqual($agent->getBaseCookieValue("b"), "BBB");
        }
    }

    class TestOfBrowserRedirects extends UnitTestCase {
        function TestOfBrowserRedirects() {
            $this->UnitTestCase();
        }
        function &createRedirect($content, $redirect) {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue('getNewCookies', array());
            $headers->setReturnValue('isRedirect', (boolean)$redirect);
            $headers->setReturnValue('getLocation', $redirect);
            $headers->setReturnValue('getNewCookies', array());
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', $content);
            $response->setReturnReference('getHeaders', $headers);
            
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference('fetch', $response);
            return $request;
        }
        function testDisabledRedirects() {
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReference(
                    '_createRequest',
                    $this->createRedirect('stuff', 'there.html'));
            $agent->expectOnce('_createRequest');
            $agent->SimpleUserAgent();
            
            $agent->setMaximumRedirects(0);
            $response = &$agent->fetchResponse('GET', 'here.html');
            
            $this->assertEqual($response->getContent(), 'stuff');
            $agent->tally();
        }
        function testSingleRedirect() {
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReferenceAt(
                    0,
                    '_createRequest',
                    $this->createRedirect('first', 'two.html'));
            $agent->setReturnReferenceAt(
                    1,
                    '_createRequest',
                    $this->createRedirect('second', 'three.html'));
            $agent->expectCallCount('_createRequest', 2);
            $agent->SimpleUserAgent();
            
            $agent->setMaximumRedirects(1);
            $response = &$agent->fetchResponse('GET', 'one.html');
            
            $this->assertEqual($response->getContent(), 'second');
            $agent->tally();
        }
        function testDoubleRedirect() {
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReferenceAt(
                    0,
                    '_createRequest',
                    $this->createRedirect('first', 'two.html'));
            $agent->setReturnReferenceAt(
                    1,
                    '_createRequest',
                    $this->createRedirect('second', 'three.html'));
            $agent->setReturnReferenceAt(
                    2,
                    '_createRequest',
                    $this->createRedirect('third', 'four.html'));
            $agent->expectCallCount('_createRequest', 3);
            $agent->SimpleUserAgent();
            
            $agent->setMaximumRedirects(2);
            $response = &$agent->fetchResponse('GET', 'one.html');
            
            $this->assertEqual($response->getContent(), 'third');
            $agent->tally();
        }
        function testSuccessAfterRedirect() {
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReferenceAt(
                    0,
                    '_createRequest',
                    $this->createRedirect('first', 'two.html'));
            $agent->setReturnReferenceAt(
                    1,
                    '_createRequest',
                    $this->createRedirect('second', false));
            $agent->setReturnReferenceAt(
                    2,
                    '_createRequest',
                    $this->createRedirect('third', 'four.html'));
            $agent->expectCallCount('_createRequest', 2);
            $agent->SimpleUserAgent();
            
            $agent->setMaximumRedirects(2);
            $response = &$agent->fetchResponse('GET', 'one.html');
            
            $this->assertEqual($response->getContent(), 'second');
            $agent->tally();
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
            
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReference('_createRequest', $request);
            $agent->SimpleUserAgent();
            
            $response = &$agent->fetchResponse('GET', 'http://this.host/this/path/page.html');
            $this->assertTrue($response->isError());
        }
    }
?>