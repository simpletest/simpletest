<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'simple_unit.php');
    require_once(SIMPLE_TEST . 'socket.php');
    require_once(SIMPLE_TEST . 'http.php');
    require_once(SIMPLE_TEST . 'simple_web_test.php');

    class LiveHttpTestCase extends UnitTestCase {
        function LiveHttpTestCase() {
            $this->UnitTestCase();
        }
        function testBadSocket() {
            @$socket = &new SimpleSocket("bad_url", 111);
            $this->assertTrue($socket->isError(), "Error [" . $socket->getError(). "]");
            $this->assertFalse($socket->isOpen());
            $this->assertFalse($socket->write("A message"));
        }
        function testSocket() {
            $socket = &new SimpleSocket("www.lastcraft.com", 80);
            $this->assertFalse($socket->isError(), "Error [" . $socket->getError(). "]");
            $this->assertTrue($socket->isOpen());
            $this->assertTrue($socket->write("GET www.lastcraft.com/test/network_confirm.php HTTP/1.0\r\n"));
            $socket->write("Host: localhost\r\n");
            $socket->write("Connection: close\r\n\r\n");
            $this->assertEqual($socket->read(8), "HTTP/1.1");
            $socket->close();
            $this->assertEqual($socket->read(8), "");
        }
        function testHttpGet() {
            $http = &new SimpleHttpRequest(new SimpleUrl(
                    "www.lastcraft.com/test/network_confirm.php?gkey=gvalue"));
            $http->setCookie(new SimpleCookie("ckey", "cvalue"));
            $this->assertIsA($response = &$http->fetch(), "SimpleHttpResponse");
            $this->assertEqual($response->getResponseCode(), 200);
            $this->assertEqual($response->getMimeType(), "text/html");
            $this->assertWantedPattern(
                    '/A target for the SimpleTest test suite/',
                    $response->getContent());
            $this->assertWantedPattern(
                    '/Request method.*?<dd>GET<\/dd>/',
                    $response->getContent());
            $this->assertWantedPattern(
                    '/gkey=\[gvalue\]/',
                    $response->getContent());
            $this->assertWantedPattern(
                    '/ckey=\[cvalue\]/',
                    $response->getContent());
        }
        function testHttpHead() {
            $http = &new SimpleHttpRequest(
                    new SimpleUrl("www.lastcraft.com/test/network_confirm.php"),
                    "HEAD");
            $this->assertIsA($response = &$http->fetch(), "SimpleHttpResponse");
            $this->assertEqual($response->getResponseCode(), 200);
            $this->assertIdentical($response->getContent(), "");
        }
        function testHttpPost() {
            $http = &new SimpleHttpPushRequest(
                    new SimpleUrl("www.lastcraft.com/test/network_confirm.php"),
                    "Some post data");
            $this->assertIsA($response = &$http->fetch(), "SimpleHttpResponse");
            $this->assertWantedPattern(
                    '/Request method.*?<dd>POST<\/dd>/',
                    $response->getContent());
            $this->assertWantedPattern(
                    '/Raw POST data.*?\s+\[Some post data\]/',
                    $response->getContent());
        }
        function testHttpFormPost() {
            $http = &new SimpleHttpPushRequest(
                    new SimpleUrl("www.lastcraft.com/test/network_confirm.php"),
                    "pkey=pvalue");
            $http->addHeaderLine('Content-Type: application/x-www-form-urlencoded');
            $response = &$http->fetch();
            $this->assertWantedPattern(
                    '/Request method.*?<dd>POST<\/dd>/',
                    $response->getContent());
            $this->assertWantedPattern(
                    '/pkey=\[pvalue\]/',
                    $response->getContent());
        }
    }
    
    class TestOfLiveFetching extends WebTestCase {
        function TestOfLiveFetching() {
            $this->WebTestCase();
        }
        function testGet() {
            $this->get('http://www.lastcraft.com/test/network_confirm.php');
            $this->assertWantedPattern('/target for the SimpleTest/');
            $this->assertWantedPattern('/Request method.*?<dd>GET<\/dd>/');
            $this->assertTitle('Simple test target file');
        }
        function testPost() {
            $this->post('http://www.lastcraft.com/test/network_confirm.php');
            $this->assertWantedPattern('/target for the SimpleTest/');
            $this->assertWantedPattern('/Request method.*?<dd>POST<\/dd>/');
        }
        function testGetWithData() {
            $this->get('http://www.lastcraft.com/test/network_confirm.php', array("a" => "aaa"));
            $this->assertWantedPattern('/Request method.*?<dd>GET<\/dd>/');
            $this->assertWantedPattern('/a=\[aaa\]/');
        }
        function testPostWithData() {
            $this->post('http://www.lastcraft.com/test/network_confirm.php', array("a" => "aaa"));
            $this->assertWantedPattern('/Request method.*?<dd>POST<\/dd>/');
            $this->assertWantedPattern('/a=\[aaa\]/');
        }
        function testRelativeGet() {
            $this->get('http://www.lastcraft.com/test/link_confirm.php');
            $this->get('./network_confirm.php');
            $this->assertWantedPattern('/target for the SimpleTest/');
        }
        function testRelativePost() {
            $this->post('http://www.lastcraft.com/test/link_confirm.php');
            $this->post('./network_confirm.php');
            $this->assertWantedPattern('/target for the SimpleTest/');
        }
        function testAbsoluteFollowing() {
            $this->get('http://www.lastcraft.com/test/link_confirm.php');
            $this->assertTrue($this->clickLink('Absolute'));
            $this->assertWantedPattern('/target for the SimpleTest/');
        }
        function testRelativeFollowing() {
            $this->get('http://www.lastcraft.com/test/link_confirm.php');
            $this->assertTrue($this->clickLink('Relative'));
            $this->assertWantedPattern('/target for the SimpleTest/');
        }
        function testIdFollowing() {
            $this->get('http://www.lastcraft.com/test/link_confirm.php');
            $this->assertTrue($this->clickLinkId(1));
            $this->assertWantedPattern('/target for the SimpleTest/');
        }
    }
    
    class TestOfLiveCookies extends WebTestCase {
        function TestOfLiveCookies() {
            $this->WebTestCase();
        }
        function testCookieSetting() {
            $this->setCookie("a", "Test cookie a", "www.lastcraft.com");
            $this->setCookie("b", "Test cookie b", "www.lastcraft.com", "test");
            $this->get('http://www.lastcraft.com/test/network_confirm.php');
            $this->assertWantedPattern('/Test cookie a/');
            $this->assertWantedPattern('/Test cookie b/');
            $this->assertCookie("a");
            $this->assertCookie("b", "Test cookie b");
        }
        function testCookieReading() {
            $this->get('http://www.lastcraft.com/test/set_cookies.php');
            $this->assertCookie("session_cookie", "A");
            $this->assertCookie("short_cookie", "B");
            $this->assertCookie("day_cookie", "C");
        }
        function testCookieExpectation() {
            $this->expectCookie("session_cookie");
            $this->get('http://www.lastcraft.com/test/set_cookies.php');
        }
        function testCookieValueExpectation() {
            $this->expectCookie("session_cookie", "A");
            $this->get('http://www.lastcraft.com/test/set_cookies.php');
        }
        function testTemporaryCookieExpiry() {
            $this->get('http://www.lastcraft.com/test/set_cookies.php');
            $this->restartSession();
            $this->assertNoCookie("session_cookie");
            $this->assertCookie("short_cookie", "B");
            $this->assertCookie("day_cookie", "C");
        }
        function testTimedCookieExpiry() {
            $this->get('http://www.lastcraft.com/test/set_cookies.php');
            $this->restartSession(time() + 101);
            $this->assertNoCookie("session_cookie");
            $this->assertNoCookie("short_cookie");
            $this->assertCookie("day_cookie", "C");
        }
        function testCookiePath() {
            $this->get('http://www.lastcraft.com/test/set_cookies.php');
            $this->assertNoCookie("path_cookie", "D");
            $this->get('./path/show_cookies.php');
            $this->assertWantedPattern('/path_cookie/');
            $this->assertCookie("path_cookie", "D");
        }
    }
?>