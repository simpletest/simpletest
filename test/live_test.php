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
            $socket = @new SimpleSocket("bad_url", 111);
            $this->assertTrue($socket->isError(), "Error [" . $socket->getError(). "]");
            $this->assertFalse($socket->isOpen());
            $this->assertFalse($socket->write("A message"));
        }
        function testSocket() {
            $socket = new SimpleSocket("www.lastcraft.com", 80);
            $this->assertFalse($socket->isError(), "Error [" . $socket->getError(). "]");
            $this->assertTrue($socket->isOpen());
            $this->assertTrue($socket->write("GET www.lastcraft.com/test/network_confirm.php HTTP/1.0\r\n"));
            $socket->write("Host: localhost\r\n");
            $socket->write("Connection: close\r\n\r\n");
            $this->assertEqual($socket->read(8), "HTTP/1.1");
            $socket->close();
            $this->assertEqual($socket->read(8), "");
        }
        function testHttp() {
            $http = new SimpleHttpRequest(new SimpleUrl(
                    "www.lastcraft.com/test/network_confirm.php?gkey=gvalue"));
            $http->setCookie(new SimpleCookie("ckey", "cvalue"));
            $this->assertIsA($reponse = &$http->fetch(), "SimpleHttpResponse");
            $this->assertEqual($reponse->getResponseCode(), 200);
            $this->assertEqual($reponse->getMimeType(), "text/html");
            $this->assertWantedPattern(
                    '/A target for the SimpleTest test suite/',
                    $reponse->getContent());
            $this->assertWantedPattern(
                    '/gkey=gvalue/',
                    $reponse->getContent());
            $this->assertWantedPattern(
                    '/ckey=cvalue/',
                    $reponse->getContent());
        }
    }
    
    class TestOfLiveFetch extends WebTestCase {
        function TestOfLiveFetch() {
            $this->WebTestCase();
        }
        function testFetch() {
            $this->fetch('http://www.lastcraft.com/test/network_confirm.php');
            $this->assertWantedPattern('/target for the SimpleTest/');
        }
        function testRelativeFetch() {
            $this->fetch('http://www.lastcraft.com/test/');
            $this->fetch('./network_confirm.php');
            $this->assertWantedPattern('/target for the SimpleTest/');
        }
    }
?>