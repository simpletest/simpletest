<?php
    // $Id$
    
    if (!defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', '../');
    }
    require_once(SIMPLE_TEST . 'fetcher.php');
    require_once(SIMPLE_TEST . 'http.php');
    Mock::generate('SimpleHttpRequest');
    Mock::generate('SimpleHttpResponse');
    Mock::generate('SimpleHttpHeaders');
    Mock::generatePartial('SimpleFetcher', 'MockRequestFetcher', array('_createRequest'));

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
            
            $fetcher = &new MockRequestFetcher($this);
            $fetcher->setReturnReference('_createRequest', $request);
            $fetcher->SimpleFetcher();
            
            $fetcher->fetchResponse(
                    'GET',
                    'http://this.com/this/path/page.html',
                    false);
            $this->assertEqual(
                    $fetcher->getCurrentUrl(),
                    "http://this.com/this/path/page.html");
            $this->assertEqual(
                    $fetcher->getBaseUrl(),
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
            
            $fetcher = &new MockRequestFetcher($this);
            $fetcher->setReturnReference('_createRequest', $request);
            $fetcher->SimpleFetcher();
            
            $fetcher->fetchResponse(
                    'POST',
                    'http://this.com/this/path/page.html',
                    false);
            $this->assertEqual(
                    $fetcher->getCurrentUrl(),
                    'http://this.com/this/path/page.html');
            $this->assertEqual(
                    $fetcher->getBaseUrl(),
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
            
            $fetcher = &new MockRequestFetcher($this);
            $fetcher->setReturnReference('_createRequest', $request);
            $fetcher->expectOnce(
                    '_createRequest',
                    array('GET', $url, array("a" => "A", "b" => "B")));
            $fetcher->SimpleFetcher();
            
            $fetcher->fetchResponse(
                    'GET',
                    'http://this.com/page.html',
                    array("a" => "A", "b" => "B"));
            $fetcher->tally();
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
            
            $fetcher = &new MockRequestFetcher($this);
            $fetcher->setReturnReference('_createRequest', $request);
            $fetcher->expectOnce(
                    '_createRequest',
                    array('HEAD', $url, array("a" => "A", "b" => "B")));
            $fetcher->SimpleFetcher();
            
            $fetcher->fetchResponse(
                    'HEAD',
                    'http://this.com/page.html',
                    array("a" => "A", "b" => "B"));
            $fetcher->tally();
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
            
            $fetcher = &new MockRequestFetcher($this);
            $fetcher->setReturnReference('_createRequest', $request);
            $fetcher->expectOnce(
                    '_createRequest',
                    array("POST", new SimpleUrl("http://this.com/page.html"), array("a" => "A", "b" => "B")));
            $fetcher->SimpleFetcher();
            
            $fetcher->fetchResponse(
                    'POST',
                    'http://this.com/page.html',
                    array("a" => "A", "b" => "B"));
            $fetcher->tally();
        }
    }
?>