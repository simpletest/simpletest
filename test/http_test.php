<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'query_string.php');
    require_once(SIMPLE_TEST . 'http.php');
    require_once(SIMPLE_TEST . 'socket.php');
    Mock::generate("SimpleSocket");

    class TestOfUrl extends UnitTestCase {
        function TestOfUrl() {
            $this->UnitTestCase();
        }
        function testDefaultUrl() {
            $url = new SimpleUrl("");
            $this->assertEqual($url->getScheme(), "");
            $this->assertEqual($url->getHost(), "");
            $this->assertEqual($url->getScheme("http"), "http");
            $this->assertEqual($url->getHost("localhost"), "localhost");
            $this->assertEqual($url->getPath(), "/");
        }
        function testBasicParsing() {
            $url = new SimpleUrl("https://www.lastcraft.com/test/");
            $this->assertEqual($url->getScheme(), "https");
            $this->assertEqual($url->getHost(), "www.lastcraft.com");
            $this->assertEqual($url->getPath(), "/test/");
        }
        function testRelativeUrls() {
            $url = new SimpleUrl("../somewhere.php");
            $this->assertEqual($url->getScheme(), false);
            $this->assertEqual($url->getHost(), false);
            $this->assertEqual($url->getPath(), "../somewhere.php");
        }
        function testParseParameter() {
            $url = new SimpleUrl('?a=A');
            $this->assertEqual($url->getPath(), '/');
            $this->assertEqual(count($request = $url->getRequest()), 1);
            $this->assertEqual($request->getValue('a'), 'A');
        }
        function testParseMultipleParameters() {
            $url = new SimpleUrl('/?a=A&b=B');
            $this->assertEqual($url->getPath(), '/');
            $request = $url->getRequest();
            $this->assertEqual($request->getValue('a'), 'A');
            $this->assertEqual($request->getValue('b'), 'B');
        }
        function testAddParameters() {
            $url = new SimpleUrl("");
            $url->addRequestParameter("a", "A");
            $request = $url->getRequest();
            $this->assertEqual($request->getValue('a'), 'A');
            $url->addRequestParameter("b", "B");
            $request = $url->getRequest();
            $this->assertEqual($request->getValue('b'), 'B');
            $url->addRequestParameter("a", "aaa");
            $request = $url->getRequest();
            $this->assertEqual($request->getValue('a'), array('A', 'aaa'));
        }
        function testEncodedParameters() {
            $url = new SimpleUrl("");
            $url->addRequestParameter('a', '?!"\'#~@[]{}:;<>,./|£$%^&*()_+-=');
            $this->assertIdentical(
                    $request = $url->getEncodedRequest(),
                    '?a=%3F%21%22%27%23%7E%40%5B%5D%7B%7D%3A%3B%3C%3E%2C.%2F%7C%A3%24%25%5E%26%2A%28%29_%2B-%3D');
            $url = new SimpleUrl('?a=%3F%21%22%27%23%7E%40%5B%5D%7B%7D%3A%3B%3C%3E%2C.%2F%7C%A3%24%25%5E%26%2A%28%29_%2B-%3D');
            $request = $url->getRequest();
            $this->assertEqual($request->getValue('a'), '?!"\'#~@[]{}:;<>,./|£$%^&*()_+-=');
        }
        function testPageSplitting() {
            $url = new SimpleUrl("./here/../there/somewhere.php");
            $this->assertEqual($url->getPath(), "./here/../there/somewhere.php");
            $this->assertEqual($url->getPage(), "somewhere.php");
            $this->assertEqual($url->getBasePath(), "./here/../there/");
        }
        function testAbsolutePathPageSplitting() {
            $url = new SimpleUrl("http://host.com/here/there/somewhere.php");
            $this->assertEqual($url->getPath(), "/here/there/somewhere.php");
            $this->assertEqual($url->getPage(), "somewhere.php");
            $this->assertEqual($url->getBasePath(), "/here/there/");
        }
        function testMakingAbsolute() {
            $url = new SimpleUrl("../there/somewhere.php");
            $this->assertEqual($url->getPath(), "../there/somewhere.php");
            $url->makeAbsolute("https://host.com/here/");
            $this->assertEqual($url->getScheme(), "https");
            $this->assertEqual($url->getHost(), "host.com");
            $this->assertEqual($url->getPath(), "/there/somewhere.php");
        }
        function testMakingAbsoluteAppendedPath() {
            $url = new SimpleUrl("./there/somewhere.php");
            $url->makeAbsolute("http://host.com/here/");
            $this->assertEqual($url->getPath(), "/here/there/somewhere.php");
            $base = new SimpleUrl("http://host.com/here/");
        }
        function testRequestEncoding() {
            $this->assertEqual(
                    SimpleUrl::encodeRequest(array('a' => '1')),
                    'a=1');
            $this->assertEqual(SimpleUrl::encodeRequest(false), '');
            $this->assertEqual(
                    SimpleUrl::encodeRequest(array('a' => array('1', '2'))),
                    'a=1&a=2');
        }
        function testBlitz() {
            $this->assertUrl(
                    "https://username:password@www.somewhere.com:243/this/that/here.php?a=1&b=2#anchor",
                    array("https", "username", "password", "www.somewhere.com", 243, "/this/that/here.php", "com", "?a=1&b=2", "anchor"),
                    array("a" => "1", "b" => "2"));
            $this->assertUrl(
                    "username:password@www.somewhere.com/this/that/here.php?a=1",
                    array(false, "username", "password", "www.somewhere.com", false, "/this/that/here.php", "com", "?a=1", false),
                    array("a" => "1"));
            $this->assertUrl(
                    "username:password@somewhere.com:243",
                    array(false, "username", "password", "somewhere.com", 243, "/", "com", "", false));
            $this->assertUrl(
                    "https://www.somewhere.com",
                    array("https", false, false, "www.somewhere.com", false, "/", "com", "", false));
            $this->assertUrl(
                    "username@www.somewhere.com:243#anchor",
                    array(false, "username", false, "www.somewhere.com", 243, "/", "com", "", "anchor"));
            $this->assertUrl(
                    "/this/that/here.php?a=1&b=2#anchor",
                    array(false, false, false, false, false, "/this/that/here.php", false, "?a=1&b=2", "anchor"),
                    array("a" => "1", "b" => "2"));
            $this->assertUrl(
                    "username@/here.php?a=1&b=2",
                    array(false, "username", false, false, false, "/here.php", false, "?a=1&b=2", false),
                    array("a" => "1", "b" => "2"));
        }
        function testAmbiguousHosts() {
            $this->assertUrl(
                    "tigger",
                    array(false, false, false, false, false, "tigger", false, "", false));
            $this->assertUrl(
                    "/tigger",
                    array(false, false, false, false, false, "/tigger", false, "", false));
            $this->assertUrl(
                    "//tigger",
                    array(false, false, false, "tigger", false, "/", false, "", false));
            $this->assertUrl(
                    "//tigger/",
                    array(false, false, false, "tigger", false, "/", false, "", false));
            $this->assertUrl(
                    "tigger.com",
                    array(false, false, false, "tigger.com", false, "/", "com", "", false));
            $this->assertUrl(
                    "me.net/tigger",
                    array(false, false, false, "me.net", false, "/tigger", "net", "", false));
        }
        function assertUrl($raw, $parts, $params = false) {
            if (! is_array($params)) {
                $params = array();
            }
            $url = new SimpleUrl($raw);
            $this->assertIdentical($url->getScheme(), $parts[0], "[$raw] scheme->%s");
            $this->assertIdentical($url->getUsername(), $parts[1], "[$raw] username->%s");
            $this->assertIdentical($url->getPassword(), $parts[2], "[$raw] password->%s");
            $this->assertIdentical($url->getHost(), $parts[3], "[$raw] host->%s");
            $this->assertIdentical($url->getPort(), $parts[4], "[$raw] port->%s");
            $this->assertIdentical($url->getPath(), $parts[5], "[$raw] path->%s");
            $this->assertIdentical($url->getTld(), $parts[6], "[$raw] tld->%s");
            $this->assertIdentical($url->getEncodedRequest(), $parts[7], "[$raw] encoded->%s");
            $query = new SimpleQueryString();
            foreach ($params as $key => $value) {
                $query->add($key, $value);
            }
            $this->assertIdentical($url->getRequest(), $query, "[$raw] request->%s");
            $this->assertIdentical($url->getFragment(), $parts[8], "[$raw] fragment->%s");
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
        function testPathValidity() {
            $cookie = new SimpleCookie("name", "value", "/path");
            $this->assertFalse($cookie->isValidPath("/"));
            $this->assertTrue($cookie->isValidPath("/path/"));
            $this->assertTrue($cookie->isValidPath("/path/more"));
        }
        function testSessionExpiring() {
            $cookie = new SimpleCookie("name", "value", "/path");
            $this->assertTrue($cookie->isExpired(0));
        }
        function testTimestampExpiry() {
            $cookie = new SimpleCookie("name", "value", "/path", 456);
            $this->assertFalse($cookie->isExpired(0));
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
        function testAging() {
            $cookie = new SimpleCookie("name", "value", "/path", 200);
            $cookie->agePrematurely(199);
            $this->assertFalse($cookie->isExpired(0));
            $cookie->agePrematurely(2);
            $this->assertTrue($cookie->isExpired(0));
        }
    }
    
    mock::generatePartial(
            'SimpleHttpRequest',
            'PartialSimpleHttpRequest',
            array('_createSocket'));

    class TestOfHttpRequest extends UnitTestCase {
        function TestOfHttpRequest() {
            $this->UnitTestCase();
        }
        function testReadingBadConnection() {
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", true);
            $request = &new PartialSimpleHttpRequest($this);
            $request->setReturnReference('_createSocket', $socket);
            $request->SimpleHttpRequest(new SimpleUrl("http://a.bad.page/"));
            $reponse = &$request->fetch();
            $this->assertTrue($reponse->isError());
        }
        function testReadingGoodConnection() {
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", false);
            $socket->expectArgumentsAt(0, "write", array("GET /and/path HTTP/1.0\r\n"));
            $socket->expectArgumentsAt(1, "write", array("Host: a.valid.host\r\n"));
            $socket->expectArgumentsAt(2, "write", array("Connection: close\r\n"));
            $socket->expectArgumentsAt(3, "write", array("\r\n"));
            $socket->expectCallCount("write", 4);
            $request = &new PartialSimpleHttpRequest($this);
            $request->setReturnReference('_createSocket', $socket);
            $request->expectArguments('_createSocket', array('a.valid.host'));
            $request->SimpleHttpRequest(new SimpleUrl("http://a.valid.host/and/path"));
            $this->assertIsA($request->fetch(), "SimpleHttpResponse");
            $socket->tally();
        }
        function testWritingGetRequest() {
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", false);
            $socket->expectArgumentsAt(0, "write", array("GET /and/path?a=A&b=B HTTP/1.0\r\n"));
            $request = &new PartialSimpleHttpRequest($this);
            $request->setReturnReference('_createSocket', $socket);
            $request->SimpleHttpRequest(new SimpleUrl("http://a.valid.host/and/path?a=A&b=B"));
            $request->fetch();
            $socket->tally();
        }
        function testWritingAdditionalHeaders() {
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", false);
            $socket->expectArgumentsAt(0, "write", array("GET /and/path HTTP/1.0\r\n"));
            $socket->expectArgumentsAt(1, "write", array("Host: a.valid.host\r\n"));
            $socket->expectArgumentsAt(2, "write", array("My: stuff\r\n"));
            $socket->expectArgumentsAt(3, "write", array("Connection: close\r\n"));
            $socket->expectArgumentsAt(4, "write", array("\r\n"));
            $socket->expectCallCount("write", 5);
            $request = &new PartialSimpleHttpRequest($this);
            $request->setReturnReference('_createSocket', $socket);
            $request->SimpleHttpRequest(new SimpleUrl("http://a.valid.host/and/path"));
            $request->addHeaderLine("My: stuff");
            $request->fetch();
            $socket->tally();
        }
        function testCookieWriting() {
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", false);
            $socket->expectArgumentsAt(0, "write", array("GET /and/path HTTP/1.0\r\n"));
            $socket->expectArgumentsAt(1, "write", array("Host: a.valid.host\r\n"));
            $socket->expectArgumentsAt(2, "write", array("Cookie: a=A\r\n"));
            $socket->expectArgumentsAt(3, "write", array("Connection: close\r\n"));
            $socket->expectArgumentsAt(4, "write", array("\r\n"));
            $socket->expectCallCount("write", 5);
            $request = &new PartialSimpleHttpRequest($this);
            $request->setReturnReference('_createSocket', $socket);
            $request->SimpleHttpRequest(new SimpleUrl("http://a.valid.host/and/path"));
            $request->setCookie(new SimpleCookie("a", "A"));
            $this->assertIsA($request->fetch(), "SimpleHttpResponse");
            $socket->tally();
        }
        function testMultipleCookieWriting() {
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", false);
            $socket->expectArgumentsAt(2, "write", array("Cookie: a=A;b=B\r\n"));
            $request = &new PartialSimpleHttpRequest($this);
            $request->setReturnReference('_createSocket', $socket);
            $request->SimpleHttpRequest(new SimpleUrl("a.valid.host/and/path"));
            $request->setCookie(new SimpleCookie("a", "A"));
            $request->setCookie(new SimpleCookie("b", "B"));
            $request->fetch();
            $socket->tally();
        }
    }
    
    class TestOfHttpHeaders extends UnitTestCase {
        function TestOfHttpHeaders() {
            $this->UnitTestCase();
        }
        function testParseBasicHeaders() {
            $headers = new SimpleHttpHeaders("HTTP/1.1 200 OK\r\n" .
                    "Date: Mon, 18 Nov 2002 15:50:29 GMT\r\n" .
                    "Content-Type: text/plain\r\n" .
                    "Server: Apache/1.3.24 (Win32) PHP/4.2.3\r\n" .
                    "Connection: close");
            $this->assertIdentical($headers->getHttpVersion(), "1.1");
            $this->assertIdentical($headers->getResponseCode(), 200);
            $this->assertEqual($headers->getMimeType(), "text/plain");
        }
        function testParseOfCookies() {
            $headers = new SimpleHttpHeaders("HTTP/1.1 200 OK\r\n" .
                    "Date: Mon, 18 Nov 2002 15:50:29 GMT\r\n" .
                    "Content-Type: text/plain\r\n" .
                    "Server: Apache/1.3.24 (Win32) PHP/4.2.3\r\n" .
                    "Set-Cookie: a=aaa; expires=Wed, 25-Dec-02 04:24:20 GMT; path=/here/\r\n" .
                    "Set-Cookie: b=bbb\r\n" .
                    "Connection: close");
            $cookies = $headers->getNewCookies();
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
        function testRedirect() {
            $headers = new SimpleHttpHeaders("HTTP/1.1 301 OK\r\n" .
                    "Content-Type: text/plain\r\n" .
                    "Content-Length: 0\r\n" .
                    "Location: http://www.somewhere-else.com/\r\n" .
                    "Connection: close");
            $this->assertIdentical($headers->getResponseCode(), 301);
            $this->assertEqual($headers->getLocation(), "http://www.somewhere-else.com/");
            $this->assertTrue($headers->isRedirect());
        }
    }
    
    Mock::generate('SimpleUrl');
    
    class TestOfHttpResponse extends UnitTestCase {
        function TestOfHttpResponse() {
            $this->UnitTestCase();
        }
        function testUrlAccessor() {
            $url = new SimpleUrl('http://www.lastcraft.com');
            $response = &new SimpleHttpResponse($url, new MockSimpleSocket($this));
            $this->assertEqual($url, $response->getUrl());
        }
        function testBadRequest() {
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", true);
            $socket->setReturnValue("getError", "Socket error");
            $response = &new SimpleHttpResponse(new MockSimpleUrl($this), $socket);
            $this->assertTrue($response->isError());
            $this->assertWantedPattern('/Socket error/', $response->getError());
            $this->assertIdentical($response->getContent(), false);
        }
        function testBadSocketDuringResponse() {
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValueAt(0, "isError", false);
            $socket->setReturnValueAt(1, "isError", false);
            $socket->setReturnValue("isError", true);
            $socket->setReturnValueAt(0, "read", "HTTP/1.1 200 OK\r\n");
            $socket->setReturnValueAt(1, "read", "Date: Mon, 18 Nov 2002 15:50:29 GMT\r\n");
            $socket->setReturnValue("read", "");
            $response = &new SimpleHttpResponse(new MockSimpleUrl($this), $socket);
            $this->assertTrue($response->isError());
            $this->assertEqual($response->getContent(), "");
        }
        function testIncompleteHeader() {
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", false);
            $socket->setReturnValueAt(0, "read", "HTTP/1.1 200 OK\r\n");
            $socket->setReturnValueAt(1, "read", "Date: Mon, 18 Nov 2002 15:50:29 GMT\r\n");
            $socket->setReturnValueAt(2, "read", "Content-Type: text/plain\r\n");
            $socket->setReturnValue("read", "");
            $response = &new SimpleHttpResponse(new MockSimpleUrl($this), $socket);
            $this->assertTrue($response->isError());
            $this->assertEqual($response->getContent(), "");
        }
        function testParseOfResponseHeaders() {
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", false);
            $socket->setReturnValueAt(0, "read", "HTTP/1.1 200 OK\r\nDate: Mon, 18 Nov 2002 15:50:29 GMT\r\n");
            $socket->setReturnValueAt(1, "read", "Content-Type: text/plain\r\n");
            $socket->setReturnValueAt(2, "read", "Server: Apache/1.3.24 (Win32) PHP/4.2.3\r\nConne");
            $socket->setReturnValueAt(3, "read", "ction: close\r\n\r\nthis is a test file\n");
            $socket->setReturnValueAt(4, "read", "with two lines in it\n");
            $socket->setReturnValue("read", "");
            $response = &new SimpleHttpResponse(new MockSimpleUrl($this), $socket);
            $this->assertFalse($response->isError());
            $this->assertEqual(
                    $response->getContent(),
                    "this is a test file\nwith two lines in it\n");
            $headers = $response->getHeaders();
            $this->assertIdentical($headers->getHttpVersion(), "1.1");
            $this->assertIdentical($headers->getResponseCode(), 200);
            $this->assertEqual($headers->getMimeType(), "text/plain");
            $this->assertFalse($headers->isRedirect());
            $this->assertFalse($headers->getLocation());
        }
        function testParseOfCookies() {
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", false);
            $socket->setReturnValueAt(0, "read", "HTTP/1.1 200 OK\r\n");
            $socket->setReturnValueAt(1, "read", "Date: Mon, 18 Nov 2002 15:50:29 GMT\r\n");
            $socket->setReturnValueAt(2, "read", "Content-Type: text/plain\r\n");
            $socket->setReturnValueAt(3, "read", "Server: Apache/1.3.24 (Win32) PHP/4.2.3\r\n");
            $socket->setReturnValueAt(4, "read", "Set-Cookie: a=aaa; expires=Wed, 25-Dec-02 04:24:20 GMT; path=/here/\r\n");
            $socket->setReturnValueAt(5, "read", "Connection: close\r\n");
            $socket->setReturnValueAt(6, "read", "\r\n");
            $socket->setReturnValue("read", "");
            $response = &new SimpleHttpResponse(new MockSimpleUrl($this), $socket);
            $this->assertFalse($response->isError());
            $headers = $response->getHeaders();
            $cookies = $headers->getNewCookies();
            $this->assertEqual($cookies[0]->getName(), "a");
            $this->assertEqual($cookies[0]->getValue(), "aaa");
            $this->assertEqual($cookies[0]->getPath(), "/here/");
            $this->assertEqual($cookies[0]->getExpiry(), "Wed, 25 Dec 2002 04:24:20 GMT");
        }
        function testRedirect() {
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue("isError", false);
            $socket->setReturnValueAt(0, "read", "HTTP/1.1 301 OK\r\n");
            $socket->setReturnValueAt(1, "read", "Content-Type: text/plain\r\n");
            $socket->setReturnValueAt(2, "read", "Location: http://www.somewhere-else.com/\r\n");
            $socket->setReturnValueAt(3, "read", "Connection: close\r\n");
            $socket->setReturnValueAt(4, "read", "\r\n");
            $socket->setReturnValue("read", "");
            $response = &new SimpleHttpResponse(new MockSimpleUrl($this), $socket);
            $headers = $response->getHeaders();
            $this->assertTrue($headers->isRedirect());
            $this->assertEqual($headers->getLocation(), "http://www.somewhere-else.com/");
        }
    }
?>