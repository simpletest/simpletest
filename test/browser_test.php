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
            $this->assertEqual($cookies["a"]->getValue(), "A");
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
            $this->assertEqual($cookies["a"]->getValue(), "A");
            $this->assertEqual($cookies["c"]->getValue(), "C");
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
        function testExpiryFilter() {
            $jar = new CookieJar();
            $jar->setCookie(new SimpleCookie("a", "A", "/", "Wed, 25-Dec-02 04:24:20 GMT"));
            $this->assertEqual(count($jar->getValidCookies(false, "/", "Wed, 25-Dec-02 04:24:19 GMT")), 1);
            $this->assertEqual(count($jar->getValidCookies(false, "/", "Wed, 25-Dec-02 04:24:21 GMT")), 0);
        }
        function testCookieMasking() {
            $jar = new CookieJar();
            $jar->setCookie(new SimpleCookie("a", "abc", "/"));
            $jar->setCookie(new SimpleCookie("a", "123", "/path/here/"));
            $cookies = $jar->getValidCookies("my-host.com", "/");
            $this->assertEqual($cookies["a"]->getPath(), "/");
            $cookies = $jar->getValidCookies("my-host.com", "/path/");
            $this->assertEqual($cookies["a"]->getPath(), "/");
            $cookies = $jar->getValidCookies("my-host.com", "/path/here");
            $this->assertEqual($cookies["a"]->getPath(), "/path/here/");
            $cookies = $jar->getValidCookies("my-host.com", "/path/here/there");
            $this->assertEqual($cookies["a"]->getPath(), "/path/here/");
            $this->assertEqual($cookies["a"]->getValue(), "123");
        }
    }

    class TestOfBrowser extends UnitTestCase {
        function TestOfBrowser() {
            $this->UnitTestCase();
        }
        function testAssertionChaining() {
            $test = &new MockUnitTestCase($this);
            $test->setExpectedArgumentsSequence(0, "assertTrue", array(true, "Good"));
            $test->setExpectedArgumentsSequence(1, "assertTrue", array(false, "Bad"));
            $test->setExpectedCallCount("assertTrue", 2);
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
            $response->setReturnValue("getContent", false);
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference("fetch", $response);
            return $request;
        }
        function testFailingBadHost() {
            $test = &new MockUnitTestCase($this);
            $test->setExpectedArgumentsSequence(0, "assertTrue", array(false, '*'));
            $test->setExpectedCallCount("assertTrue", 1);
            $browser = &new TestBrowser($test);
            $request = &$this->_createSimulatedBadHost();
            $this->assertIdentical(
                    $browser->fetchUrl("http://this.host/this/path/page.html", &$request),
                    false);
            $test->tally();
        }
        function testExpectingBadHost() {
            $test = &new MockUnitTestCase($this);
            $test->setExpectedArgumentsSequence(0, "assertTrue", array(true, '*'));
            $test->setExpectedCallCount("assertTrue", 1);
            $browser = &new TestBrowser($test);
            $browser->expectBadConnection();
            $request = &$this->_createSimulatedBadHost();
            $this->assertIdentical(
                    $browser->fetchUrl("http://this.host/this/path/page.html", &$request),
                    false);
            $test->tally();
        }
    }
    
    class testOfBrowserCookies extends UnitTestCase {
        function TestOfBrowserCookies() {
            $this->UnitTestCase();
        }
        function testSend() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue("isError", false);
            $response->setReturnValue("getContent", "stuff");
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference("fetch", $response);
            $request->setExpectedArguments("setCookie", array(new SimpleCookie("a", "A")));
            $request->setExpectedCallCount("setCookie", 1);
            $browser = &new TestBrowser(new MockUnitTestCase($this));
            $browser->setCookie(new SimpleCookie("a", "A"));
            $browser->fetchUrl("http://this.host/this/path/page.html", &$request);
            $request->tally();
        }
        function testReceive() {
        }
    }
?>