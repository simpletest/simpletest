<?php
    // $Id$

    class TestOfCookie extends UnitTestCase {
        
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

    class TestOfCookieJar extends UnitTestCase {
        
        function testAddCookie() {
            $jar = new SimpleCookieJar();
            $jar->replaceCookie("a", "A");
            $cookies = $jar->getValidCookies();
            $this->assertEqual(count($cookies), 1);
            $this->assertEqual($cookies[0]->getValue(), "A");
        }
        
        function testHostFilter() {
            $jar = new SimpleCookieJar();
            $jar->replaceCookie('a', 'A', 'my-host.com');
            $jar->replaceCookie('b', 'B', 'another-host.com');
            $jar->replaceCookie('c', 'C');
            $cookies = $jar->getValidCookies('my-host.com');
            
            $this->assertEqual(count($cookies), 2);
            $this->assertEqual($cookies[0]->getValue(), 'A');
            $this->assertEqual($cookies[1]->getValue(), 'C');
            $this->assertEqual(count($jar->getValidCookies('another-host.com')), 2);
            $this->assertEqual(count($jar->getValidCookies('www.another-host.com')), 2);
            $this->assertEqual(count($jar->getValidCookies('new-host.org')), 1);
            $this->assertEqual(count($jar->getValidCookies()), 3);
        }
        
        function testPathFilter() {
            $jar = new SimpleCookieJar();
            $jar->replaceCookie('a', 'A', false, '/path/');
            $this->assertEqual(count($jar->getValidCookies(false, "/")), 0);
            $this->assertEqual(count($jar->getValidCookies(false, "/elsewhere")), 0);
            $this->assertEqual(count($jar->getValidCookies(false, "/path/")), 1);
            $this->assertEqual(count($jar->getValidCookies(false, "/path")), 1);
            $this->assertEqual(count($jar->getValidCookies(false, "/pa")), 0);
            $this->assertEqual(count($jar->getValidCookies(false, "/path/here/")), 1);
        }
        
        function testPathFilterDeeply() {
            $jar = new SimpleCookieJar();
            $jar->replaceCookie('a', 'A', false, '/path/more_path/');
            $this->assertEqual(count($jar->getValidCookies(false, "/path/")), 0);
            $this->assertEqual(count($jar->getValidCookies(false, "/path")), 0);
            $this->assertEqual(count($jar->getValidCookies(false, "/pa")), 0);
            $this->assertEqual(count($jar->getValidCookies(false, "/path/more_path/")), 1);
            $this->assertEqual(count($jar->getValidCookies(false, "/path/more_path/and_more")), 1);
            $this->assertEqual(count($jar->getValidCookies(false, "/path/not_here/")), 0);
        }
        
        function testMultipleCookieWithDifferentPaths() {
            $jar = new SimpleCookieJar();
            $jar->replaceCookie('a', 'abc', false, '/');
            $jar->replaceCookie('a', '123', false, '/path/here/');
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
            $jar = new SimpleCookieJar();
            $jar->replaceCookie('a', 'abc', false, '/');
            $jar->replaceCookie('a', 'cde', false, '/');
            $cookies = $jar->getValidCookies();
            $this->assertIdentical($cookies[0]->getValue(), "cde");
        }
        
        function testClearSessionCookies() {
            $jar = new SimpleCookieJar();
            $jar->replaceCookie('a', 'A', false, '/');
            $jar->restartSession();
            $this->assertEqual(count($jar->getValidCookies(false, "/")), 0);
        }
        
        function testExpiryFilterByDate() {
            $jar = new SimpleCookieJar();
            $jar->replaceCookie('a', 'A', false, '/', 'Wed, 25-Dec-02 04:24:20 GMT');
            $jar->restartSession("Wed, 25-Dec-02 04:24:19 GMT");
            $this->assertIdentical(
                    $jar->getValidCookies(false, "/"),
                    array(new SimpleCookie('a', 'A', '/', 'Wed, 25-Dec-02 04:24:20 GMT')));
            $jar->restartSession("Wed, 25-Dec-02 04:24:21 GMT");
            $this->assertIdentical($jar->getValidCookies(false, '/'), array());
        }
        
        function testExpiryFilterByAgeing() {
            $jar = new SimpleCookieJar();
            $jar->replaceCookie('a', 'A', false, '/', 'Wed, 25-Dec-02 04:24:20 GMT');
            $jar->restartSession("Wed, 25-Dec-02 04:24:19 GMT");
            $this->assertIdentical(
                    $jar->getValidCookies(false, '/'),
                    array(new SimpleCookie('a', 'A', '/', 'Wed, 25-Dec-02 04:24:20 GMT')));
            $jar->agePrematurely(2);
            $jar->restartSession("Wed, 25-Dec-02 04:24:19 GMT");
            $this->assertIdentical($list = $jar->getValidCookies(false, '/'), array());
        }
        
        function testCookieClearing() {
            $jar = new SimpleCookieJar();
            $jar->replaceCookie('a', 'abc', false, '/');
            $jar->replaceCookie('a', '', false, '/');
            $this->assertEqual(count($cookies = $jar->getValidCookies(false, '/')), 1);
            $this->assertIdentical($cookies[0]->getValue(), '');
        }
        
        function testCookieClearByDate() {
            $jar = new SimpleCookieJar();
            $jar->replaceCookie('a', 'abc', false, '/', 'Wed, 25-Dec-02 04:24:21 GMT');
            $jar->replaceCookie('a', 'def', false, '/', 'Wed, 25-Dec-02 04:24:19 GMT');
            $cookies = $jar->getValidCookies(false, '/');
            $this->assertIdentical($cookies[0]->getValue(), 'def');
            $jar->restartSession('Wed, 25-Dec-02 04:24:20 GMT');
            $this->assertEqual(count($jar->getValidCookies(false, '/')), 0);
        }
    }
?>