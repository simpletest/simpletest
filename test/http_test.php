<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'http.php');
    Mock::generate("SimpleSocket");

    class CookieTestCase extends UnitTestCase {
        function CookieTestCase() {
            $this->UnitTestCase();
        }
        function testCookieAccessors() {
            $cookie = new SimpleCookie(
                    "name",
                    "value",
                    "/path",
                    "Mon, 18 Nov 2002 15:50:29 GMT");
            $this->assertEqual($cookie->getName(), "name");
            $this->assertEqual($cookie->getValue(), "value");
            $this->assertEqual($cookie->getPath(), "/path");
            $this->assertEqual($cookie->getExpiry(), "Mon, 18 Nov 2002 15:50:29 GMT");
        }
        function testCookieDefaults() {
            $cookie = new SimpleCookie("name");
            $this->assertFalse($cookie->getValue());
            $this->assertEqual($cookie->getPath(), "/");
            $this->assertEqual($cookie->getHost(), "localhost");
            $this->assertFalse($cookie->getExpiry());
        }
        function testHostname() {
            $cookie = new SimpleCookie("name");
            $cookie->setHost("hostname.here");
            $this->assertEqual($cookie->getHost(), "hostname.here");
        }
    }

    class HttpRequestTestCase extends UnitTestCase {
        function HttpRequestTestCase() {
            $this->UnitTestCase();
        }
        function testReadingBadConnection() {
            $request = new SimpleHttpRequest("http://a.bad.page/");
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", true);
            $this->assertFalse($request->fetch(&$socket));
        }
        function testReadingGoodConnection() {
            $request = new SimpleHttpRequest("http://a.valid.page/and/path");
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", false);
            $socket->setExpectedArgumentsSequence(0, "write", array("GET a.valid.page/and/path HTTP/1.0\r\n"));
            $socket->setExpectedArgumentsSequence(1, "write", array("Host: localhost\r\n"));
            $socket->setExpectedArgumentsSequence(2, "write", array("Connection: close\r\n"));
            $socket->setExpectedArgumentsSequence(3, "write", array("\r\n"));
            $socket->setExpectedCallCount("write", 4);
            $this->assertIsA($request->fetch(&$socket), "SimpleHttpResponse");
            $socket->tally();
        }
        function testCookieWriting() {
            $request = new SimpleHttpRequest("http://a.valid.page/and/path");
            $request->setCookie(new SimpleCookie("a", "A"));
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", false);
            $socket->setExpectedArgumentsSequence(0, "write", array("GET a.valid.page/and/path HTTP/1.0\r\n"));
            $socket->setExpectedArgumentsSequence(1, "write", array("Host: localhost\r\n"));
            $socket->setExpectedArgumentsSequence(2, "write", array("Cookie: a=A\r\n"));
            $socket->setExpectedArgumentsSequence(3, "write", array("Connection: close\r\n"));
            $socket->setExpectedArgumentsSequence(4, "write", array("\r\n"));
            $socket->setExpectedCallCount("write", 5);
            $this->assertIsA($request->fetch(&$socket), "SimpleHttpResponse");
            $socket->tally();
        }
        function testMultipleCookieWriting() {
            $request = new SimpleHttpRequest("http://a.valid.page/and/path");
            $request->setCookie(new SimpleCookie("a", "A"));
            $request->setCookie(new SimpleCookie("b", "B"));
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", false);
            $socket->setExpectedArgumentsSequence(2, "write", array("Cookie: a=A;b=B\r\n"));
            $request->fetch(&$socket);
            $socket->tally();
        }
    }
    
    class HttpResponseTestCase extends UnitTestCase {
        function HttpResponseTestCase() {
            $this->UnitTestCase();
        }
        function testBadRequest() {
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", true);
            $socket->setReturnValue("getError", "Socket error");
            $response = &new SimpleHttpResponse($socket);
            $this->assertTrue($response->isError());
            $this->assertWantedPattern('/Socket error/', $response->getError());
        }
        function testReadAll() {
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", false);
            $socket->setReturnValueSequence(0, "read", "aaa");
            $socket->setReturnValueSequence(1, "read", "bbb");
            $socket->setReturnValueSequence(2, "read", "ccc");
            $socket->setReturnValue("read", "");
            $this->assertEqual(SimpleHttpResponse::_readAll($socket), "aaabbbccc");
        }
        function testBadSocketDuringResponse() {
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValueSequence(0, "isError", false);
            $socket->setReturnValueSequence(1, "isError", false);
            $socket->setReturnValue("isError", true);
            $socket->setReturnValueSequence(0, "read", "HTTP/1.1 200 OK\r\n");
            $socket->setReturnValueSequence(1, "read", "Date: Mon, 18 Nov 2002 15:50:29 GMT\r\n");
            $socket->setReturnValue("read", "");
            $response = &new SimpleHttpResponse($socket);
            $this->assertTrue($response->isError());
            $this->assertEqual($response->getContent(), "");
        }
        function testIncompleteHeader() {
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", false);
            $socket->setReturnValueSequence(0, "read", "HTTP/1.1 200 OK\r\n");
            $socket->setReturnValueSequence(1, "read", "Date: Mon, 18 Nov 2002 15:50:29 GMT\r\n");
            $socket->setReturnValueSequence(2, "read", "Content-Type: text/plain\r\n");
            $socket->setReturnValue("read", "");
            $response = &new SimpleHttpResponse($socket);
            $this->assertTrue($response->isError());
            $this->assertEqual($response->getContent(), "");
        }
        function testParseOfResponse() {
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", false);
            $socket->setReturnValueSequence(0, "read", "HTTP/1.1 200 OK\r\nDate: Mon, 18 Nov 2002 15:50:29 GMT\r\n");
            $socket->setReturnValueSequence(1, "read", "Content-Type: text/plain\r\n");
            $socket->setReturnValueSequence(2, "read", "Server: Apache/1.3.24 (Win32) PHP/4.2.3\r\nConne");
            $socket->setReturnValueSequence(3, "read", "ction: close\r\n\r\nthis is a test file\n");
            $socket->setReturnValueSequence(4, "read", "with two lines in it\n");
            $socket->setReturnValue("read", "");
            $response = &new SimpleHttpResponse($socket);
            $this->assertFalse($response->isError());
            $this->assertEqual(
                    $response->getContent(),
                    "this is a test file\nwith two lines in it\n");
            $this->assertIdentical($response->getHttpVersion(), "1.1");
            $this->assertIdentical($response->getResponseCode(), 200);
            $this->assertEqual($response->getMimeType(), "text/plain");
        }
        function testParseOfCookies() {
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", false);
            $socket->setReturnValueSequence(0, "read", "HTTP/1.1 200 OK\r\n");
            $socket->setReturnValueSequence(1, "read", "Date: Mon, 18 Nov 2002 15:50:29 GMT\r\n");
            $socket->setReturnValueSequence(2, "read", "Content-Type: text/plain\r\n");
            $socket->setReturnValueSequence(3, "read", "Server: Apache/1.3.24 (Win32) PHP/4.2.3\r\n");
            $socket->setReturnValueSequence(4, "read", "Set-Cookie: a=aaa; expires=Wed, 25-Dec-02 04:24:20 GMT; path=/here/\r\n");
            $socket->setReturnValueSequence(5, "read", "Set-Cookie: b=bbb\r\n");
            $socket->setReturnValueSequence(6, "read", "Connection: close\r\n");
            $socket->setReturnValueSequence(7, "read", "\r\n");
            $socket->setReturnValueSequence(8, "read", "this is a test file\n");
            $socket->setReturnValue("read", "");
            $response = &new SimpleHttpResponse($socket);
            $this->assertFalse($response->isError());
            $cookies = $response->getNewCookies();
            $this->assertEqual(count($cookies), 2);
            $this->assertEqual($cookies[0]->getName(), "a");
            $this->assertEqual($cookies[0]->getValue(), "aaa");
            $this->assertEqual($cookies[0]->getPath(), "/here/");
            $this->assertEqual($cookies[0]->getExpiry(), "Wed, 25-Dec-02 04:24:20 GMT");
            $this->assertEqual($cookies[1]->getName(), "b");
            $this->assertEqual($cookies[1]->getValue(), "bbb");
            $this->assertEqual($cookies[1]->getPath(), "/");
            $this->assertEqual($cookies[1]->getExpiry(), "");
        }
    }
    
    class LiveHttpTestCase extends UnitTestCase {
        function LiveHttpTestCase() {
            $this->UnitTestCase();
        }
        function testRealPageFetch() {
            $http = new SimpleHttpRequest("www.lastcraft.com/test/network_confirm.php");
            $this->assertIsA($http->fetch(), "SimpleHttpResponse");
        }
    }
?>