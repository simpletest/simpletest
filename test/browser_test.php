<?php
    // $Id$
    
    if (!defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', '../');
    }
    require_once(SIMPLE_TEST . 'browser.php');
    require_once(SIMPLE_TEST . 'user_agent.php');
    require_once(SIMPLE_TEST . 'http.php');
    Mock::generate('SimpleHttpResponse');
    Mock::generate('SimpleHttpHeaders');
    Mock::generate('SimplePage');
    Mock::generate('SimpleForm');
    Mock::generate('SimpleUserAgent');
    Mock::generatePartial(
            'SimpleBrowser',
            'MockParseSimpleBrowser',
            array('_createUserAgent', '_parse'));
    
    class TestOfParsedPageAccess extends UnitTestCase {
        function TestOfParsedPageAccess() {
            $this->UnitTestCase();
        }
        function &loadPage(&$page) {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue('getMimeType', 'text/html');
            $headers->setReturnValue('getResponseCode', 200);
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', 'stuff');
            $response->setReturnReference('getHeaders', $headers);
            
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', $response);
            
            $browser = &new MockParseSimpleBrowser($this);
            $browser->setReturnReference('_createUserAgent', $agent);
            $browser->setReturnReference('_parse', $page);
            $browser->expectOnce('_parse', array('stuff'));
            $browser->SimpleBrowser();
            
            $browser->get('http://this.com/page.html');
            return $browser;
        }
        function testParse() {
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getRaw', 'Raw HTML');
            $page->setReturnValue('getTitle', 'Here');
            
            $browser = &$this->loadPage($page);

            $this->assertEqual($browser->getContent(), 'Raw HTML');
            $this->assertEqual($browser->getTitle(), 'Here');
            $this->assertIdentical($browser->getResponseCode(), 200);
            $this->assertEqual($browser->getMimeType(), 'text/html');
            $browser->tally();
        }
        function testFormHandling() {
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getField', 'Value');
            $page->expectOnce('getField', array('key'));
            $page->expectOnce('setField', array('key', 'Value'));
            
            $browser = &$this->loadPage($page);
            $this->assertEqual($browser->getField('key'), 'Value');
            
            $browser->setField('key', 'Value');
            $page->tally();
        }
    }
    
    class TestOfBrowserNavigation extends UnitTestCase {
        function TestOfBrowserNavigation() {
            $this->UnitTestCase();
        }
        function &getSuccessfulFetch() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue('getMimeType', 'text/html');
            $headers->setReturnValue('getResponseCode', 200);
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', 'stuff');
            $response->setReturnReference('getHeaders', $headers);
            return $response;
        }
        function &createBrowser(&$agent, &$page) {
            $browser = &new MockParseSimpleBrowser($this);
            $browser->setReturnReference('_createUserAgent', $agent);
            $browser->setReturnReference('_parse', $page);
            $browser->SimpleBrowser();
            return $browser;
        }
        function testClickLinkRequestsPage() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', $this->getSuccessfulFetch());
            $agent->expectArgumentsAt(
                    0,
                    'fetchResponse',
                    array('GET', 'http://this.com/page.html', false));
            $agent->expectArgumentsAt(
                    1,
                    'fetchResponse',
                    array('GET', 'new.html', false));
            $agent->expectCallCount('fetchResponse', 2);
            
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getUrls', array('new.html'));
            $page->expectOnce('getUrls', array('New'));
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->clickLink('New'));
            
            $agent->tally();
            $page->tally();
        }
        function testClickingMissingLinkFails() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', $this->getSuccessfulFetch());
            
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getUrls', array());
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertFalse($browser->clickLink('New'));
        }
        function testClickIndexedLink() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', $this->getSuccessfulFetch());
            $agent->expectArgumentsAt(
                    1,
                    'fetchResponse',
                    array('GET', '1.html', false));
            $agent->expectCallCount('fetchResponse', 2);
            
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getUrls', array('0.html', '1.html'));
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->clickLink('New', 1));
            
            $agent->tally();
        }
        function testClinkLinkById() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', $this->getSuccessfulFetch());
            $agent->expectArgumentsAt(
                    1,
                    'fetchResponse',
                    array('GET', 'link.html', false));
            $agent->expectCallCount('fetchResponse', 2);
            
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getUrlById', 'link.html');
            $page->expectOnce('getUrlById', array(2));
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->clickLinkById(2));
            
            $agent->tally();
            $page->tally();
        }
        function testClickingMissingLinkIdFails() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', $this->getSuccessfulFetch());
            
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getUrlById', false);
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertFalse($browser->clickLink(0));
        }
        function testSubmitFormByLabel() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', $this->getSuccessfulFetch());
            $agent->expectArgumentsAt(
                    1,
                    'fetchResponse',
                    array('POST', 'handler.html', array('a' => 'A')));
            $agent->expectCallCount('fetchResponse', 2);
            
            $form = &new MockSimpleForm($this);
            $form->setReturnValue('getAction', 'handler.html');
            $form->setReturnValue('getMethod', 'post');
            $form->setReturnvalue('submitButtonByLabel', array('a' => 'A'));
            
            $page = &new MockSimplePage($this);
            $page->setReturnReference('getFormBySubmitLabel', $form);
            $page->expectOnce('getFormBySubmitLabel', array('Go'));
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->clickSubmit('Go'));
            
            $agent->tally();
            $page->tally();
        }
        function testDefaultSubmitFormByLabel() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', $this->getSuccessfulFetch());
            $agent->expectArgumentsAt(
                    1,
                    'fetchResponse',
                    array('GET', 'http://this.com/page.html', array('a' => 'A')));
            $agent->expectCallCount('fetchResponse', 2);
            $agent->setReturnValue('getCurrentUrl', 'http://this.com/page.html');
            
            $form = &new MockSimpleForm($this);
            $form->setReturnValue('getAction', false);
            $form->setReturnValue('getMethod', 'get');
            $form->setReturnvalue('submitButtonByLabel', array('a' => 'A'));
            
            $page = &new MockSimplePage($this);
            $page->setReturnReference('getFormBySubmitLabel', $form);
            $page->expectOnce('getFormBySubmitLabel', array('Submit'));
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->clickSubmit());
            
            $agent->tally();
            $page->tally();
        }
        function testSubmitFormById() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', $this->getSuccessfulFetch());
            $agent->expectArgumentsAt(
                    1,
                    'fetchResponse',
                    array('POST', 'handler.html', array('a' => 'A')));
            $agent->expectCallCount('fetchResponse', 2);
            
            $form = &new MockSimpleForm($this);
            $form->setReturnValue('getAction', 'handler.html');
            $form->setReturnValue('getMethod', 'post');
            $form->setReturnvalue('submit', array('a' => 'A'));
            
            $page = &new MockSimplePage($this);
            $page->setReturnReference('getFormById', $form);
            $page->expectOnce('getFormById', array(33));
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->submitFormById(33));
            
            $agent->tally();
            $page->tally();
        }
    }
?>