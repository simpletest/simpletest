<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'http.php');
    require_once(SIMPLE_TEST . 'socket.php');
    Mock::generate("SimpleSocket");

    class TestOfUrl extends UnitTestCase {
        function TestOfUrl() {
            $this->UnitTestCase();
        }
        function testDefaultUrl() {
            $url = new SimpleUrl("");
            $this->assertEqual($url->getScheme(), "http");
            $this->assertEqual($url->getHost(), "localhost");
            $this->assertEqual($url->getPath(), "/");
        }
        function testBasicParsing() {
            $url = new SimpleUrl("https://www.lastcraft.com/test/");
            $this->assertEqual($url->getScheme(), "https");
            $this->assertEqual($url->getHost(), "www.lastcraft.com");
            $this->assertEqual($url->getPath(), "/test/");
        }
        function testParseParameter() {
            $url = new SimpleUrl("?a=A");
            $this->assertEqual($url->getPath(), "/");
            $this->assertEqual(count($request = $url->getRequest()), 1);
            $this->assertEqual($request["a"], "A");
        }
        function testParseMultipleParameters() {
            $url = new SimpleUrl("/?a=A&b=B");
            $this->assertEqual($url->getPath(), "/");
            $this->assertEqual(count($request = $url->getRequest()), 2);
            $this->assertEqual($request["a"], "A");
            $this->assertEqual($request["b"], "B");
        }
        function testAddParameters() {
            $url = new SimpleUrl("");
            $url->addRequestParameter("a", "A");
            $this->assertEqual(count($request = $url->getRequest()), 1);
            $this->assertEqual($request["a"], "A");
            $url->addRequestParameter("b", "B");
            $this->assertEqual(count($request = $url->getRequest()), 2);
            $this->assertEqual($request["b"], "B");
            $url->addRequestParameter("a", "aaa");
            $this->assertEqual(count($request = $url->getRequest()), 2);
            $this->assertEqual($request["a"], "aaa");
        }
        function testEncodedParameters() {
            $url = new SimpleUrl("");
            $url->addRequestParameter("a", '?!"\'#~@[]{}:;<>,./|£$%^&*()_+-=');
            $this->assertIdentical(
                    $request = $url->getEncodedRequest(),
                    "?a=%3F%21%22%27%23%7E%40%5B%5D%7B%7D%3A%3B%3C%3E%2C.%2F%7C%A3%24%25%5E%26%2A%28%29_%2B-%3D");
            $url = new SimpleUrl("?a=%3F%21%22%27%23%7E%40%5B%5D%7B%7D%3A%3B%3C%3E%2C.%2F%7C%A3%24%25%5E%26%2A%28%29_%2B-%3D");
            $request = $url->getRequest();
            $this->assertEqual($request["a"], '?!"\'#~@[]{}:;<>,./|£$%^&*()_+-=');
        }
    }

    class TestOfCookie extends UnitTestCase {
        function TestOfCookie() {
            $this->UnitTestCase();
        }
        function testCookieDefaults() {
            $cookie = new SimpleCookie("name");
            $this->assertFalse($cookie->getValue());
            $this->assertEqual($cookie->getPath(), "/");
            $this->assertIdentical($cookie->getHost(), false);
            $this->assertFalse($cookie->getExpiry());
            $this->assertFalse($cookie->isSecure());
        }
        function testCookieAccessors() {
            $cookie = new SimpleCookie(
                    "name",
                    "value",
                    "/path",
                    "Mon, 18 Nov 2002 15:50:29 GMT",
                    true);
            $this->assertEqual($cookie->getName(), "name");
            $this->assertEqual($cookie->getValue(), "value");
            $this->assertEqual($cookie->getPath(), "/path/");
            $this->assertEqual($cookie->getExpiry(), "Mon, 18 Nov 2002 15:50:29 GMT");
            $this->assertTrue($cookie->isSecure());
        }
        function testFullHostname() {
            $cookie = new SimpleCookie("name");
            $this->assertTrue($cookie->setHost("host.name.here"));
            $this->assertEqual($cookie->getHost(), "host.name.here");
            $this->assertTrue($cookie->setHost("host.com"));
            $this->assertEqual($cookie->getHost(), "host.com");
        }
        function testHostTruncation() {
            $cookie = new SimpleCookie("name");
            $cookie->setHost("this.host.name.here");
            $this->assertEqual($cookie->getHost(), "host.name.here");
            $cookie->setHost("this.host.com");
            $this->assertEqual($cookie->getHost(), "host.com");
            $this->assertTrue($cookie->setHost("dashes.in-host.com"));
            $this->assertEqual($cookie->getHost(), "in-host.com");
        }
        function testBadHosts() {
            $cookie = new SimpleCookie("name");
            $this->assertFalse($cookie->setHost("gibberish"));
            $this->assertFalse($cookie->setHost("host.here"));
            $this->assertFalse($cookie->setHost("host..com"));
            $this->assertFalse($cookie->setHost("..."));
            $this->assertFalse($cookie->setHost("host.com."));
        }
        function testHostValidity() {
            $cookie = new SimpleCookie("name");
            $cookie->setHost("this.host.name.here");
            $this->assertTrue($cookie->isValidHost("host.name.here"));
            $this->assertTrue($cookie->isValidHost("that.host.name.here"));
            $this->assertFalse($cookie->isValidHost("bad.host"));
            $this->assertFalse($cookie->isValidHost("nearly.name.here"));
        }
        function testNonExpiring() {
            $cookie = new SimpleCookie("name", "value", "/path");
            $this->assertFalse($cookie->isExpired(0));
        }
        function testTimestampExpiry() {
            $cookie = new SimpleCookie("name", "value", "/path", 456);
            $this->assertTrue($cookie->isExpired(457));
            $this->assertFalse($cookie->isExpired(455));
        }
        function testDateExpiry() {
            $cookie = new SimpleCookie(
                    "name",
                    "value",
                    "/path",
                    "Mon, 18 Nov 2002 15:50:29 GMT");
            $this->assertTrue($cookie->isExpired("Mon, 18 Nov 2002 15:50:30 GMT"));
            $this->assertFalse($cookie->isExpired("Mon, 18 Nov 2002 15:50:28 GMT"));
        }
    }

    class TestOfHttpRequest extends UnitTestCase {
        function TestOfHttpRequest() {
            $this->UnitTestCase();
        }
        function testReadingBadConnection() {
            $request = new SimpleHttpRequest("http://a.bad.page/");
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", true);
            $this->assertFalse($request->fetch(&$socket));
        }
        function testReadingGoodConnection() {
            $request = new SimpleHttpRequest("http://a.valid.host/and/path");
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", false);
            $socket->expectArgumentsSequence(0, "write", array("GET /and/path HTTP/1.0\r\n"));
            $socket->expectArgumentsSequence(1, "write", array("Host: a.valid.host\r\n"));
            $socket->expectArgumentsSequence(2, "write", array("Connection: close\r\n"));
            $socket->expectArgumentsSequence(3, "write", array("\r\n"));
            $socket->expectCallCount("write", 4);
            $this->assertIsA($request->fetch(&$socket), "SimpleHttpResponse");
            $socket->tally();
        }
        function testWritingGetRequest() {
            $request = new SimpleHttpRequest("http://a.valid.host/and/path?a=A&b=B");
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", false);
            $socket->expectArgumentsSequence(0, "write", array("GET /and/path?a=A&b=B HTTP/1.0\r\n"));
            $request->fetch(&$socket);
            $socket->tally();
        }
        function testWritingAdditionalHeaders() {
            $request = new SimpleHttpRequest("http://a.valid.host/and/path");
            $request->addHeaderLine("My: stuff");
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", false);
            $socket->expectArgumentsSequence(0, "write", array("GET /and/path HTTP/1.0\r\n"));
            $socket->expectArgumentsSequence(1, "write", array("Host: a.valid.host\r\n"));
            $socket->expectArgumentsSequence(2, "write", array("My: stuff\r\n"));
            $socket->expectArgumentsSequence(3, "write", array("Connection: close\r\n"));
            $socket->expectArgumentsSequence(4, "write", array("\r\n"));
            $socket->expectCallCount("write", 5);
            $request->fetch(&$socket);
            $socket->tally();
        }
        function testCookieWriting() {
            $request = new SimpleHttpRequest("http://a.valid.host/and/path");
            $request->setCookie(new SimpleCookie("a", "A"));
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", false);
            $socket->expectArgumentsSequence(0, "write", array("GET /and/path HTTP/1.0\r\n"));
            $socket->expectArgumentsSequence(1, "write", array("Host: a.valid.host\r\n"));
            $socket->expectArgumentsSequence(2, "write", array("Cookie: a=A\r\n"));
            $socket->expectArgumentsSequence(3, "write", array("Connection: close\r\n"));
            $socket->expectArgumentsSequence(4, "write", array("\r\n"));
            $socket->expectCallCount("write", 5);
            $this->assertIsA($request->fetch(&$socket), "SimpleHttpResponse");
            $socket->tally();
        }
        function testMultipleCookieWriting() {
            $request = new SimpleHttpRequest("a.valid.host/and/path");
            $request->setCookie(new SimpleCookie("a", "A"));
            $request->setCookie(new SimpleCookie("b", "B"));
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", false);
            $socket->expectArgumentsSequence(2, "write", array("Cookie: a=A;b=B\r\n"));
            $request->fetch(&$socket);
            $socket->tally();
        }
    }
    
    class TestOfHttpResponse extends UnitTestCase {
        function TestOfHttpResponse() {
            $this->UnitTestCase();
        }
        function testBadRequest() {
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", true);
            $socket->setReturnValue("getError", "Socket error");
            $response = &new SimpleHttpResponse($socket);
            $this->assertTrue($response->isError());
            $this->assertWantedPattern('/Socket error/', $response->getError());
            $this->assertIdentical($response->getContent(), false);
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
            $socket->setReturnValue("read", "");
            $response = &new SimpleHttpResponse($socket);
            $this->assertFalse($response->isError());
            $cookies = $response->getNewCookies();
            $this->assertEqual(count($cookies), 2);
            $this->assertEqual($cookies[0]->getName(), "a");
            $this->assertEqual($cookies[0]->getValue(), "aaa");
            $this->assertEqual($cookies[0]->getPath(), "/here/");
            $this->assertEqual($cookies[0]->getExpiry(), "Wed, 25 Dec 2002 04:24:20 GMT");
            $this->assertEqual($cookies[1]->getName(), "b");
            $this->assertEqual($cookies[1]->getValue(), "bbb");
            $this->assertEqual($cookies[1]->getPath(), "/");
            $this->assertEqual($cookies[1]->getExpiry(), "");
        }
    }
?>