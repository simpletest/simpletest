<?php
    // $Id$
    require_once(dirname(__FILE__) . '/../unit_tester.php');
    require_once(dirname(__FILE__) . '/../socket.php');
    require_once(dirname(__FILE__) . '/../http.php');
    require_once(dirname(__FILE__) . '/../options.php');
    require_once(dirname(__FILE__) . '/../browser.php');
    require_once(dirname(__FILE__) . '/../web_tester.php');

    if (SimpleTestOptions::getDefaultProxy()) {
        SimpleTestOptions::ignore('LiveHttpTestCase');
    }
    class LiveHttpTestCase extends UnitTestCase {
        function LiveHttpTestCase() {
            $this->UnitTestCase();
        }
        function testBadSocket() {
            @$socket = &new SimpleSocket("bad_url", 111, 5);
            $this->swallowErrors();
            $this->assertTrue($socket->isError(), "Error [" . $socket->getError(). "]");
            $this->assertFalse($socket->isOpen());
            $this->assertFalse($socket->write("A message"));
        }
        function testSocket() {
            $socket = &new SimpleSocket("www.lastcraft.com", 80, 15);
            $this->assertFalse($socket->isError(), "Error [" . $socket->getError(). "]");
            $this->assertTrue($socket->isOpen());
            $this->assertTrue($socket->write("GET www.lastcraft.com/test/network_confirm.php HTTP/1.0\r\n"));
            $socket->write("Host: localhost\r\n");
            $socket->write("Connection: close\r\n\r\n");
            $this->assertEqual($socket->read(8), "HTTP/1.1");
            $socket->close();
            $this->assertIdentical($socket->read(8), false);
        }
    }
    
    class TestOfLiveBrowser extends UnitTestCase {
        function TestOfLiveBrowser() {
            $this->UnitTestCase();
        }
        function testGet() {
            $browser = &new SimpleBrowser();
            $browser->addHeader('User-Agent: SimpleTest ' . SimpleTestOptions::getVersion());
            
            $this->assertTrue($browser->get('http://www.lastcraft.com/test/network_confirm.php'));
            $this->assertWantedPattern('/target for the SimpleTest/', $browser->getContent());
            $this->assertWantedPattern('/Request method.*?<dd>GET<\/dd>/', $browser->getContent());
            $this->assertEqual($browser->getTitle(), 'Simple test target file');
            $this->assertEqual($browser->getResponseCode(), 200);
            $this->assertEqual($browser->getMimeType(), "text/html");
        }
        function testPost() {
            $browser = &new SimpleBrowser();
            $browser->addHeader('User-Agent: SimpleTest ' . SimpleTestOptions::getVersion());
            $this->assertTrue($browser->post('http://www.lastcraft.com/test/network_confirm.php'));
            $this->assertWantedPattern('/target for the SimpleTest/', $browser->getContent());
            $this->assertWantedPattern('/Request method.*?<dd>POST<\/dd>/', $browser->getContent());
        }
        function testAbsoluteLinkFollowing() {
            $browser = &new SimpleBrowser();
            $browser->addHeader('User-Agent: SimpleTest ' . SimpleTestOptions::getVersion());
            $browser->get('http://www.lastcraft.com/test/link_confirm.php');
            $this->assertTrue($browser->clickLink('Absolute'));
            $this->assertWantedPattern('/target for the SimpleTest/', $browser->getContent());
        }
        function testRelativeLinkFollowing() {
            $browser = &new SimpleBrowser();
            $browser->addHeader('User-Agent: SimpleTest ' . SimpleTestOptions::getVersion());
            $browser->get('http://www.lastcraft.com/test/link_confirm.php');
            $this->assertTrue($browser->clickLink('Relative'));
            $this->assertWantedPattern('/target for the SimpleTest/', $browser->getContent());
        }
        function testIdFollowing() {
            $browser = &new SimpleBrowser();
            $browser->addHeader('User-Agent: SimpleTest ' . SimpleTestOptions::getVersion());
            $browser->get('http://www.lastcraft.com/test/link_confirm.php');
            $this->assertTrue($browser->clickLinkById(1));
            $this->assertWantedPattern('/target for the SimpleTest/', $browser->getContent());
        }
        function testCookieReading() {
            $browser = &new SimpleBrowser();
            $browser->addHeader('User-Agent: SimpleTest ' . SimpleTestOptions::getVersion());
            $browser->get('http://www.lastcraft.com/test/set_cookies.php');
            $this->assertEqual($browser->getBaseCookieValue('session_cookie'), 'A');
            $this->assertEqual($browser->getBaseCookieValue('short_cookie'), 'B');
            $this->assertEqual($browser->getBaseCookieValue('day_cookie'), 'C');
        }
        function testSimpleSubmit() {
            $browser = &new SimpleBrowser();
            $browser->addHeader('User-Agent: SimpleTest ' . SimpleTestOptions::getVersion());
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
        function setUp() {
            $this->addHeader('User-Agent: SimpleTest ' . SimpleTestOptions::getVersion());
        }
        function testGet() {
            $this->assertTrue($this->get('http://www.lastcraft.com/test/network_confirm.php'));
            $this->assertWantedPattern('/target for the SimpleTest/');
            $this->assertWantedPattern('/Request method.*?<dd>GET<\/dd>/');
            $this->assertTitle('Simple test target file');
            $this->assertResponse(200);
            $this->assertMime('text/html');
        }
        function testSlowGet() {
            $this->assertTrue($this->get('http://www.lastcraft.com/test/slow_page.php'));
        }
        function testTimedOutGet() {
            $this->setConnectionTimeout(1);
            $this->assertFalse($this->get('http://www.lastcraft.com/test/slow_page.php'));
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
            $this->assertLink('Absolute');
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
            $this->assertLinkById(1);
            $this->assertTrue($this->clickLinkById(1));
            $this->assertWantedPattern('/target for the SimpleTest/');
        }
    }
    
    class TestOfLiveRedirects extends WebTestCase {
        function TestOfLiveRedirects() {
            $this->WebTestCase();
        }
        function setUp() {
            $this->addHeader('User-Agent: SimpleTest ' . SimpleTestOptions::getVersion());
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
        function testRedirectLosesGetData() {
            $this->get('http://www.lastcraft.com/test/redirect.php', array('a' => 'aaa'));
            $this->assertNoUnwantedPattern('/a=\[aaa\]/');
        }
        function testRedirectKeepsExtraRequestDataOfItsOwn() {
            $this->get('http://www.lastcraft.com/test/redirect.php');
            $this->assertWantedPattern('/r=\[rrr\]/');
        }
        function testRedirectLosesPostData() {
            $this->post('http://www.lastcraft.com/test/redirect.php', array('a' => 'aaa'));
            $this->assertTitle('Simple test target file');
            $this->assertNoUnwantedPattern('/a=\[aaa\]/');
        }
        function testRedirectWithBaseUrlChange() {
            $this->get('http://www.lastcraft.com/test/base_change_redirect.php');
            $this->assertTitle('Simple test target file in folder');
            $this->get('http://www.lastcraft.com/test/path/base_change_redirect.php');
            $this->assertTitle('Simple test target file');
        }
        function testRedirectWithDoubleBaseUrlChange() {
            $this->get('http://www.lastcraft.com/test/double_base_change_redirect.php');
            $this->assertTitle('Simple test target file');
        }
    }
    
    class TestOfLiveCookies extends WebTestCase {
        function TestOfLiveCookies() {
            $this->WebTestCase();
        }
        function setUp() {
            $this->addHeader('User-Agent: SimpleTest ' . SimpleTestOptions::getVersion());
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
            $this->restartSession(time() + 160);        // Allows sixty second drift.
            $this->assertNoCookie(
                    "short_cookie",
                    "%s->Please check your computer clock setting if you are not using NTP");
        }
        function testOfClockUnderDrift() {
            $this->get('http://www.lastcraft.com/test/set_cookies.php');
            $this->restartSession(time() + 40);         // Allows sixty second drift.
            $this->assertCookie(
                    "short_cookie",
                    "B",
                    "%s->Please check your computer clock setting if you are not using NTP");
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
        function setUp() {
            $this->addHeader('User-Agent: SimpleTest ' . SimpleTestOptions::getVersion());
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
        function testFormSubmissionByLabel() {
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
        function testFormSubmissionByName() {
            $this->get('http://www.lastcraft.com/test/form.html');
            $this->assertTrue($this->clickSubmitByName('go'));
            $this->assertWantedPattern('/go=\[Go!\]/');
        }
        function testFormSubmissionById() {
            $this->get('http://www.lastcraft.com/test/form.html');
            $this->assertTrue($this->clickSubmitById(99));
            $this->assertWantedPattern('/go=\[Go!\]/');
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
        function setUp() {
            $this->addHeader('User-Agent: SimpleTest ' . SimpleTestOptions::getVersion());
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
    
    class TestOfHistoryNavigation extends WebTestCase {
        function TestOfHistoryNavigation() {
            $this->WebTestCase();
        }
        function setUp() {
            $this->addHeader('User-Agent: SimpleTest ' . SimpleTestOptions::getVersion());
        }
        function testRetry() {
            $this->get('http://www.lastcraft.com/test/cookie_based_counter.php');
            $this->assertWantedPattern('/count: 1/i');
            $this->retry();
            $this->assertWantedPattern('/count: 2/i');
            $this->retry();
            $this->assertWantedPattern('/count: 3/i');
        }
        function testOfBackButton() {
            $this->get('http://www.lastcraft.com/test/1.html');
            $this->clickLink('2');
            $this->assertTitle('2');
            $this->assertTrue($this->back());
            $this->assertTitle('1');
            $this->assertTrue($this->forward());
            $this->assertTitle('2');
            $this->assertFalse($this->forward());
        }
        function testGetRetryResubmitsData() {
            $this->assertTrue($this->get(
                    'http://www.lastcraft.com/test/network_confirm.php?a=aaa'));
            $this->assertWantedPattern('/Request method.*?<dd>GET<\/dd>/');
            $this->assertWantedPattern('/a=\[aaa\]/');
            $this->retry();
            $this->assertWantedPattern('/Request method.*?<dd>GET<\/dd>/');
            $this->assertWantedPattern('/a=\[aaa\]/');
        }
        function testGetRetryResubmitsExtraData() {
            $this->assertTrue($this->get(
                    'http://www.lastcraft.com/test/network_confirm.php',
                    array('a' => 'aaa')));
            $this->assertWantedPattern('/Request method.*?<dd>GET<\/dd>/');
            $this->assertWantedPattern('/a=\[aaa\]/');
            $this->retry();
            $this->assertWantedPattern('/Request method.*?<dd>GET<\/dd>/');
            $this->assertWantedPattern('/a=\[aaa\]/');
        }
        function testPostRetryResubmitsData() {
            $this->assertTrue($this->post(
                    'http://www.lastcraft.com/test/network_confirm.php',
                    array('a' => 'aaa')));
            $this->assertWantedPattern('/Request method.*?<dd>POST<\/dd>/');
            $this->assertWantedPattern('/a=\[aaa\]/');
            $this->retry();
            $this->assertWantedPattern('/Request method.*?<dd>POST<\/dd>/');
            $this->assertWantedPattern('/a=\[aaa\]/');
        }
        function testGetRetryResubmitsRepeatedData() {
            $this->assertTrue($this->get(
                    'http://www.lastcraft.com/test/network_confirm.php?a=1&a=2'));
            $this->assertWantedPattern('/Request method.*?<dd>GET<\/dd>/');
            $this->assertWantedPattern('/a=\[1, 2\]/');
            $this->retry();
            $this->assertWantedPattern('/Request method.*?<dd>GET<\/dd>/');
            $this->assertWantedPattern('/a=\[1, 2\]/');
        }
    }
    
    class TestOfAuthentication extends WebTestCase {
        function TestOfAuthentication() {
            $this->WebTestCase();
        }
        function setUp() {
            $this->addHeader('User-Agent: SimpleTest ' . SimpleTestOptions::getVersion());
        }
        function testChallengeFromProtectedPage() {
            $this->get('http://www.lastcraft.com/test/protected/');
            $this->assertResponse(401);
            $this->assertAuthentication('Basic');
            $this->assertRealm('SimpleTest basic authentication');
            $this->authenticate('test', 'secret');
            $this->assertResponse(200);
            $this->retry();
            $this->assertResponse(200);
        }
        function testEncodedAuthenticationFetchesPage() {
            $this->get('http://test:secret@www.lastcraft.com/test/protected/');
            $this->assertResponse(200);
        }
        function testRealmExtendsToWholeDirectory() {
            $this->get('http://www.lastcraft.com/test/protected/1.html');
            $this->authenticate('test', 'secret');
            $this->clickLink('2');
            $this->assertResponse(200);
            $this->clickLink('3');
            $this->assertResponse(200);
        }
        function testRedirectKeepsAuthentication() {
            $this->get('http://www.lastcraft.com/test/protected/local_redirect.php');
            $this->authenticate('test', 'secret');
            $this->assertTitle('Simple test target file');
        }
    }
    
    class TestOfFrames extends WebTestCase {
        function TestOfFrames() {
            $this->WebTestCase();
        }
        function setUp() {
            $this->addHeader('User-Agent: SimpleTest ' . SimpleTestOptions::getVersion());
        }
        function testNoFramesContentWhenFramesDisabled() {
            $this->ignoreFrames();
            $this->get('http://www.lastcraft.com/test/one_page_frameset.html');
            $this->assertTitle('Frameset for testing of SimpleTest');
            $this->assertWantedPattern('/This content is for no frames only/');
        }
        function testTitleTakenFromFramesetPage() {
            $this->get('http://www.lastcraft.com/test/one_page_frameset.html');
            $this->assertTitle('Frameset for testing of SimpleTest');
        }
        function testPatternMatchCanReadTheOnlyFrame() {
            $this->get('http://www.lastcraft.com/test/one_page_frameset.html');
            $this->assertWantedPattern('/A target for the SimpleTest test suite/');
            $this->assertNoUnwantedPattern('/This content is for no frames only/');
        }
        function testReadingContentFromFocusedFrames() {
            $this->get('http://www.lastcraft.com/test/frameset.html');
            $this->assertWantedPattern('/This is frame A/i');
            $this->assertWantedPattern('/This is frame B/i');
            
            $this->setFrameFocus('aaa');
            $this->assertWantedPattern('/This is frame A/i');
            $this->assertNoUnwantedPattern('/This is frame B/i');
            
            $this->setFrameFocus('bbb');
            $this->assertNoUnwantedPattern('/This is frame A/i');
            $this->assertWantedPattern('/This is frame B/i');
            
            $this->clearFrameFocus();
            $this->assertWantedPattern('/This is frame A/i');
            $this->assertWantedPattern('/This is frame B/i');
        }
    }
?>