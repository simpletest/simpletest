<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'unit_tester.php');
    require_once(SIMPLE_TEST . 'socket.php');
    require_once(SIMPLE_TEST . 'http.php');
    require_once(SIMPLE_TEST . 'browser.php');
    require_once(SIMPLE_TEST . 'web_tester.php');

    class LiveHttpTestCase extends UnitTestCase {
        function LiveHttpTestCase() {
            $this->UnitTestCase();
        }
        function testBadSocket() {
            @$socket = &new SimpleSocket("bad_url", 111);
            $this->swallowErrors();
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
            $this->assertIdentical($socket->read(8), false);
        }
        function testHttpGet() {
            $http = &new SimpleHttpRequest(new SimpleUrl(
                    "www.lastcraft.com/test/network_confirm.php?gkey=gvalue"));
            $http->setCookie(new SimpleCookie("ckey", "cvalue"));
            $this->assertIsA($response = &$http->fetch(), "SimpleHttpResponse");
            $headers = &$response->getHeaders();
            $this->assertEqual($headers->getResponseCode(), 200);
            $this->assertEqual($headers->getMimeType(), "text/html");
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
            $headers = &$response->getHeaders();
            $this->assertEqual($headers->getResponseCode(), 200);
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
    
    class TestOfLiveBrowser extends UnitTestCase {
        function TestOfLiveBrowser() {
            $this->UnitTestCase();
        }
        function testGet() {
            $browser = &new SimpleBrowser();
            $this->assertTrue($browser->get('http://www.lastcraft.com/test/network_confirm.php'));
            $this->assertWantedPattern('/target for the SimpleTest/', $browser->getContent());
            $this->assertWantedPattern('/Request method.*?<dd>GET<\/dd>/', $browser->getContent());
            $this->assertEqual($browser->getTitle(), 'Simple test target file');
            $this->assertEqual($browser->getResponseCode(), 200);
            $this->assertEqual($browser->getMimeType(), "text/html");
        }
        function testPost() {
            $browser = &new SimpleBrowser();
            $this->assertTrue($browser->post('http://www.lastcraft.com/test/network_confirm.php'));
            $this->assertWantedPattern('/target for the SimpleTest/', $browser->getContent());
            $this->assertWantedPattern('/Request method.*?<dd>POST<\/dd>/', $browser->getContent());
        }
        function testAbsoluteLinkFollowing() {
            $browser = &new SimpleBrowser();
            $browser->get('http://www.lastcraft.com/test/link_confirm.php');
            $this->assertTrue($browser->clickLink('Absolute'));
            $this->assertWantedPattern('/target for the SimpleTest/', $browser->getContent());
        }
        function testRelativeLinkFollowing() {
            $browser = &new SimpleBrowser();
            $browser->get('http://www.lastcraft.com/test/link_confirm.php');
            $this->assertTrue($browser->clickLink('Relative'));
            $this->assertWantedPattern('/target for the SimpleTest/', $browser->getContent());
        }
        function testIdFollowing() {
            $browser = &new SimpleBrowser();
            $browser->get('http://www.lastcraft.com/test/link_confirm.php');
            $this->assertTrue($browser->clickLinkById(1));
            $this->assertWantedPattern('/target for the SimpleTest/', $browser->getContent());
        }
        function testCookieReading() {
            $browser = &new SimpleBrowser();
            $browser->get('http://www.lastcraft.com/test/set_cookies.php');
            $this->assertEqual($browser->getBaseCookieValue("session_cookie"), "A");
            $this->assertEqual($browser->getBaseCookieValue("short_cookie"), "B");
            $this->assertEqual($browser->getBaseCookieValue("day_cookie"), "C");
        }
        function testSimpleSubmit() {
            $browser = &new SimpleBrowser();
            $browser->get('http://www.lastcraft.com/test/form.html');
            $this->assertTrue($browser->clickSubmit('Go!'));
            $this->assertWantedPattern('/Request method.*?<dd>POST<\/dd>/', $browser->getContent());
            $this->assertWantedPattern('/go=\[Go!\]/', $browser->getContent());
        }
    }
    
    class TestOfLiveFetching extends WebTestCase {
        function TestOfLiveFetching() {
            $this->WebTestCase();
        }
        function testGet() {
            $this->assertTrue($this->get('http://www.lastcraft.com/test/network_confirm.php'));
            $this->assertWantedPattern('/target for the SimpleTest/');
            $this->assertWantedPattern('/Request method.*?<dd>GET<\/dd>/');
            $this->assertTitle('Simple test target file');
            $this->assertResponse(200);
            $this->assertMime("text/html");
        }
        function testPost() {
            $this->assertTrue($this->post('http://www.lastcraft.com/test/network_confirm.php'));
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
            $this->assertTrue($this->get('network_confirm.php'));
            $this->assertWantedPattern('/target for the SimpleTest/');
        }
        function testRelativePost() {
            $this->post('http://www.lastcraft.com/test/link_confirm.php');
            $this->assertTrue($this->post('network_confirm.php'));
            $this->assertWantedPattern('/target for the SimpleTest/');
        }
        function testAbsoluteLinkFollowing() {
            $this->get('http://www.lastcraft.com/test/link_confirm.php');
            $this->assertTrue($this->clickLink('Absolute'));
            $this->assertWantedPattern('/target for the SimpleTest/');
        }
        function testRelativeLinkFollowing() {
            $this->get('http://www.lastcraft.com/test/link_confirm.php');
            $this->assertTrue($this->clickLink('Relative'));
            $this->assertWantedPattern('/target for the SimpleTest/');
        }
        function testIdFollowing() {
            $this->get('http://www.lastcraft.com/test/link_confirm.php');
            $this->assertTrue($this->clickLinkById(1));
            $this->assertWantedPattern('/target for the SimpleTest/');
        }
    }
    
    class TestOfLiveRedirects extends WebTestCase {
        function TestOfLiveRedirects() {
            $this->WebTestCase();
        }
        function testNoRedirects() {
            $this->setMaximumRedirects(0);
            $this->get('http://www.lastcraft.com/test/redirect.php');
            $this->assertTitle('Redirection test');
        }
        function testRedirects() {
            $this->setMaximumRedirects(1);
            $this->get('http://www.lastcraft.com/test/redirect.php');
            $this->assertTitle('Simple test target file');
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
        function testTemporaryCookieExpiry() {
            $this->get('http://www.lastcraft.com/test/set_cookies.php');
            $this->restartSession();
            $this->assertNoCookie("session_cookie");
            $this->assertCookie("short_cookie", "B");
            $this->assertCookie("day_cookie", "C");
        }
        function testTimedCookieExpiry() {
            $this->get('http://www.lastcraft.com/test/set_cookies.php');
            $this->ageCookies(3600);
            $this->restartSession(time() + 60);    // Includes a 60 sec. clock drift margin.
            $this->assertNoCookie("session_cookie");
            $this->assertNoCookie("hour_cookie");
            $this->assertCookie("day_cookie", "C");
        }
        function testOfClockOverDrift() {
            $this->get('http://www.lastcraft.com/test/set_cookies.php');
            $this->restartSession(time() + 160);        // Allows sixty second wire time.
            $this->assertNoCookie(
                    "short_cookie",
                    "%s->Please check you computer clock setting if you are not using NTP");
        }
        function testOfClockUnderDrift() {
            $this->get('http://www.lastcraft.com/test/set_cookies.php');
            $this->restartSession(time() + 40);         // Allows sixty second wire time.
            $this->assertCookie(
                    "short_cookie",
                    "B",
                    "%s->Please check you computer clock setting if you are not using NTP");
        }
        function testCookiePath() {
            $this->get('http://www.lastcraft.com/test/set_cookies.php');
            $this->assertNoCookie("path_cookie", "D");
            $this->get('./path/show_cookies.php');
            $this->assertWantedPattern('/path_cookie/');
            $this->assertCookie("path_cookie", "D");
        }
    }
    
    class TestOfLiveForm extends WebTestCase {
        function TestOfLiveForm() {
            $this->WebTestCase();
        }
        function testSimpleSubmit() {
            $this->get('http://www.lastcraft.com/test/form.html');
            $this->assertTrue($this->clickSubmit('Go!'));
            $this->assertWantedPattern('/Request method.*?<dd>POST<\/dd>/');
            $this->assertWantedPattern('/go=\[Go!\]/');
        }
        function testDefaultFormValues() {
            $this->get('http://www.lastcraft.com/test/form.html');
            $this->assertField('a', '');
            $this->assertField('b', 'Default text');
            $this->assertField('c', '');
            $this->assertField('d', 'd1');
            $this->assertField('e', false);
            $this->assertField('f', 'on');
            $this->assertField('g', 'g3');
            $this->assertTrue($this->clickSubmit('Go!'));
            $this->assertWantedPattern('/go=\[Go!\]/');
            $this->assertWantedPattern('/a=\[\]/');
            $this->assertWantedPattern('/b=\[Default text\]/');
            $this->assertWantedPattern('/c=\[\]/');
            $this->assertWantedPattern('/d=\[d1\]/');
            $this->assertNoUnwantedPattern('/e=\[/');
            $this->assertWantedPattern('/f=\[on\]/');
            $this->assertWantedPattern('/g=\[g3\]/');
        }
        function testFormSubmission() {
            $this->get('http://www.lastcraft.com/test/form.html');
            $this->setField('a', 'aaa');
            $this->setField('b', 'bbb');
            $this->setField('c', 'ccc');
            $this->setField('d', 'D2');
            $this->setField('e', 'on');
            $this->setField('f', false);
            $this->setField('g', 'g2');
            $this->assertTrue($this->clickSubmit('Go!'));
            $this->assertWantedPattern('/a=\[aaa\]/');
            $this->assertWantedPattern('/b=\[bbb\]/');
            $this->assertWantedPattern('/c=\[ccc\]/');
            $this->assertWantedPattern('/d=\[d2\]/');
            $this->assertWantedPattern('/e=\[on\]/');
            $this->assertNoUnwantedPattern('/f=\[/');
            $this->assertWantedPattern('/g=\[g2\]/');
        }
        function testSelfSubmit() {
            $this->get('http://www.lastcraft.com/test/self_form.php');
            $this->assertNoUnwantedPattern('/<p>submitted<\/p>/i');
            $this->assertNoUnwantedPattern('/<p>wrong form<\/p>/i');
            $this->assertTitle('Test of form self submission');
            $this->assertTrue($this->clickSubmit());
            $this->assertWantedPattern('/<p>submitted<\/p>/i');
            $this->assertNoUnwantedPattern('/<p>wrong form<\/p>/i');
            $this->assertTitle('Test of form self submission');
        }
    }
    
    class TestOfMultiValueWidgets extends WebTestCase {
        function TestOfMultiValueWidgets() {
            $this->WebTestCase();
        }
        function testDefaultFormValueSubmission() {
            $this->get('http://www.lastcraft.com/test/multiple_widget_form.html');
            $this->assertField('a', array('a2', 'a3'));
            $this->assertField('b', array('b2', 'b3'));
            $this->assertTrue($this->clickSubmit('Go!'));
            $this->assertWantedPattern('/a=\[a2, a3\]/');
            $this->assertWantedPattern('/b=\[b2, b3\]/');
        }
        function testSubmittingMultipleValues() {
            $this->get('http://www.lastcraft.com/test/multiple_widget_form.html');
            $this->setField('a', array('a1', 'a4'));
            $this->assertField('a', array('a1', 'a4'));
            $this->setField('b', array('b1', 'b4'));
            $this->assertField('b', array('b1', 'b4'));
            $this->assertTrue($this->clickSubmit('Go!'));
            $this->assertWantedPattern('/a=\[a1, a4\]/');
            $this->assertWantedPattern('/b=\[b1, b4\]/');
        }
    }
    
    class TestOfFrames extends WebTestCase {
        function TestOfFrames() {
            $this->WebTestCase();
        }
        function testNoFramesContentWhenFramesDisabled() {
            $this->ignoreFrames();
            $this->get('http://www.lastcraft.com/test/frameset.html');
            $this->assertTitle('Frameset for testing of SimpleTest');
            $this->assertWantedPattern('/This content is for no frames only/');
        }
    }
?>