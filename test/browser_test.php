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
            $jar->setCookie(new SimpleCookie("a", "A", "/"));
            $cookies = $jar->getValidCookies("/");
            $this->assertEqual(count($cookies), 1);
            $this->assertEqual($cookies[0]->getValue(), "A");
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
            $browser->expectFail();
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