<?php
    // $Id$
    
    require_once(dirname(__FILE__) . '/../browser.php');
    require_once(dirname(__FILE__) . '/../user_agent.php');
    require_once(dirname(__FILE__) . '/../http.php');
    require_once(dirname(__FILE__) . '/../page.php');
    
    Mock::generate('SimpleHttpResponse');
    Mock::generate('SimplePage');
    Mock::generate('SimpleForm');
    Mock::generate('SimpleUserAgent');
    Mock::generatePartial(
            'SimpleBrowser',
            'MockParseSimpleBrowser',
            array('_createUserAgent', '_parse'));
    
    class TestOfHistory extends UnitTestCase {
        function TestOfHistory() {
            $this->UnitTestCase();
        }
        function testEmptyHistoryHasFalseContents() {
            $history = &new SimpleBrowserHistory();
            $this->assertIdentical($history->getMethod(), false);
            $this->assertIdentical($history->getUrl(), false);
            $this->assertIdentical($history->getParameters(), false);
        }
        function testCannotMoveInEmptyHistory() {
            $history = &new SimpleBrowserHistory();
            $this->assertFalse($history->back());
            $this->assertFalse($history->forward());
        }
        function testCurrentTargetAccessors() {
            $history = &new SimpleBrowserHistory();
            $history->recordEntry('GET', new SimpleUrl('http://www.here.com/'), array());
            $this->assertIdentical($history->getMethod(), 'GET');
            $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.here.com/'));
            $this->assertIdentical($history->getParameters(), array());
        }
        function testSecondEntryAccessors() {
            $history = &new SimpleBrowserHistory();
            $history->recordEntry('GET', new SimpleUrl('http://www.first.com/'), array());
            $history->recordEntry('POST', new SimpleUrl('http://www.second.com/'), array('a' => 1));
            $this->assertIdentical($history->getMethod(), 'POST');
            $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.second.com/'));
            $this->assertIdentical($history->getParameters(), array('a' => 1));
        }
        function testGoingBackwards() {
            $history = &new SimpleBrowserHistory();
            $history->recordEntry('GET', new SimpleUrl('http://www.first.com/'), array());
            $history->recordEntry('POST', new SimpleUrl('http://www.second.com/'), array('a' => 1));
            $this->assertTrue($history->back());
            $this->assertIdentical($history->getMethod(), 'GET');
            $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.first.com/'));
            $this->assertIdentical($history->getParameters(), array());
        }
        function testGoingBackwardsOffBeginning() {
            $history = &new SimpleBrowserHistory();
            $history->recordEntry('GET', new SimpleUrl('http://www.first.com/'), array());
            $this->assertFalse($history->back());
            $this->assertIdentical($history->getMethod(), 'GET');
            $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.first.com/'));
            $this->assertIdentical($history->getParameters(), array());
        }
        function testGoingForwardsOffEnd() {
            $history = &new SimpleBrowserHistory();
            $history->recordEntry('GET', new SimpleUrl('http://www.first.com/'), array());
            $this->assertFalse($history->forward());
            $this->assertIdentical($history->getMethod(), 'GET');
            $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.first.com/'));
            $this->assertIdentical($history->getParameters(), array());
        }
        function testGoingBackwardsAndForwards() {
            $history = &new SimpleBrowserHistory();
            $history->recordEntry('GET', new SimpleUrl('http://www.first.com/'), array());
            $history->recordEntry('POST', new SimpleUrl('http://www.second.com/'), array('a' => 1));
            $this->assertTrue($history->back());
            $this->assertTrue($history->forward());
            $this->assertIdentical($history->getMethod(), 'POST');
            $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.second.com/'));
            $this->assertIdentical($history->getParameters(), array('a' => 1));
        }
        function testNewEntryReplacesNextOne() {
            $history = &new SimpleBrowserHistory();
            $history->recordEntry('GET', new SimpleUrl('http://www.first.com/'), array());
            $history->recordEntry('POST', new SimpleUrl('http://www.second.com/'), array('a' => 1));
            $history->back();
            $history->recordEntry('GET', new SimpleUrl('http://www.third.com/'), array());
            $this->assertIdentical($history->getMethod(), 'GET');
            $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.third.com/'));
            $this->assertIdentical($history->getParameters(), array());
        }
        function testNewEntryDropsFutureEntries() {
            $history = &new SimpleBrowserHistory();
            $history->recordEntry('GET', new SimpleUrl('http://www.first.com/'), array());
            $history->recordEntry('GET', new SimpleUrl('http://www.second.com/'), array());
            $history->recordEntry('GET', new SimpleUrl('http://www.third.com/'), array());
            $history->back();
            $history->back();
            $history->recordEntry('GET', new SimpleUrl('http://www.fourth.com/'), array());
            $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.fourth.com/'));
            $this->assertFalse($history->forward());
            $history->back();
            $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.first.com/'));
            $this->assertFalse($history->back());
        }
    }
    
    class TestOfParsedPageAccess extends UnitTestCase {
        function TestOfParsedPageAccess() {
            $this->UnitTestCase();
        }
        function &loadPage(&$page) {
            $response = &new MockSimpleHttpResponse($this);
            
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', $response);
            
            $browser = &new MockParseSimpleBrowser($this);
            $browser->setReturnReference('_createUserAgent', $agent);
            $browser->setReturnReference('_parse', $page);
            $browser->SimpleBrowser();
            
            $browser->get('http://this.com/page.html');
            return $browser;
        }
        function testAccessorsWhenNoPage() {
            $agent = &new MockSimpleUserAgent($this);
            
            $browser = &new MockParseSimpleBrowser($this);
            $browser->setReturnReference('_createUserAgent', $agent);
            $browser->SimpleBrowser();
            
            $this->assertEqual($browser->getContent(), '');
        }
        function testParse() {
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getRequest', "GET here.html\r\n\r\n");
            $page->setReturnValue('getRaw', 'Raw HTML');
            $page->setReturnValue('getTitle', 'Here');
            $page->setReturnValue('getFrameFocus', 'Frame');
            $page->setReturnValue('getMimeType', 'text/html');
            $page->setReturnValue('getResponseCode', 200);
            $page->setReturnValue('getAuthentication', 'Basic');
            $page->setReturnValue('getRealm', 'Somewhere');
            $page->setReturnValue('getTransportError', 'Ouch!');
            
            $browser = &$this->loadPage($page);

            $this->assertEqual($browser->getRequest(), "GET here.html\r\n\r\n");
            $this->assertEqual($browser->getContent(), 'Raw HTML');
            $this->assertEqual($browser->getTitle(), 'Here');
            $this->assertEqual($browser->getFrameFocus(), 'Frame');
            $this->assertIdentical($browser->getResponseCode(), 200);
            $this->assertEqual($browser->getMimeType(), 'text/html');
            $this->assertEqual($browser->getAuthentication(), 'Basic');
            $this->assertEqual($browser->getRealm(), 'Somewhere');
            $this->assertEqual($browser->getTransportError(), 'Ouch!');
        }
        function testLinkAffirmationWhenPresent() {
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getUrlsByLabel', array('http://www.nowhere.com'));
            $page->expectOnce('getUrlsByLabel', array('a link label'));
            
            $browser = &$this->loadPage($page);
            $this->assertTrue($browser->isLink('a link label'));
            
            $page->tally();
        }
        function testLinkAffirmationByIdWhenPresent() {
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getUrlById', true, array(99));
            $page->setReturnValue('getUrlById', false, array('*'));
            
            $browser = &$this->loadPage($page);
            $this->assertTrue($browser->isLinkById(99));
            $this->assertFalse($browser->isLinkById(98));
            
            $page->tally();
        }
        function testFormHandling() {
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getField', 'Value');
            $page->expectOnce('getField', array('key'));
            $page->expectOnce('setField', array('key', 'Value'));
            $page->setReturnValue('getFieldById', 'Id value');
            $page->expectOnce('getFieldById', array(99));
            $page->expectOnce('setFieldById', array(99, 'Id value'));

            $browser = &$this->loadPage($page);
            $this->assertEqual($browser->getField('key'), 'Value');            
            $this->assertEqual($browser->getFieldById(99), 'Id value');            
            $browser->setField('key', 'Value');
            $browser->setFieldById(99, 'Id value');
            
            $page->tally();
        }
    }
    
    class TestOfBrowserNavigation extends UnitTestCase {
        function TestOfBrowserNavigation() {
            $this->UnitTestCase();
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
            $agent->setReturnReference('fetchResponse', new MockSimpleHttpResponse($this));
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
            $page->setReturnValue('getUrlsByLabel', array('new.html'));
            $page->expectOnce('getUrlsByLabel', array('New'));
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->clickLink('New'));
            
            $agent->tally();
            $page->tally();
        }
        function testClickingMissingLinkFails() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', new MockSimpleHttpResponse($this));
            
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getUrlsByLabel', array());
            $page->setReturnValue('getRaw', 'stuff');
            
            $browser = &$this->createBrowser($agent, $page);
            $this->assertTrue($browser->get('http://this.com/page.html'));
            $this->assertFalse($browser->clickLink('New'));
        }
        function testClickIndexedLink() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', new MockSimpleHttpResponse($this));
            $agent->expectArgumentsAt(
                    1,
                    'fetchResponse',
                    array('GET', '1.html', false));
            $agent->expectCallCount('fetchResponse', 2);
            
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getUrlsByLabel', array('0.html', '1.html'));
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->clickLink('New', 1));
            
            $agent->tally();
        }
        function testClinkLinkById() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', new MockSimpleHttpResponse($this));
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
            $agent->setReturnReference('fetchResponse', new MockSimpleHttpResponse($this));
            
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getUrlById', false);
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertFalse($browser->clickLink(0));
        }
        function testSubmitFormByLabel() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', new MockSimpleHttpResponse($this));
            $agent->expectArgumentsAt(
                    1,
                    'fetchResponse',
                    array('POST', 'handler.html', array('a' => 'A')));
            $agent->expectCallCount('fetchResponse', 2);
            
            $form = &new MockSimpleForm($this);
            $form->setReturnValue('getAction', 'handler.html');
            $form->setReturnValue('getMethod', 'post');
            $form->setReturnValue('submitButtonByLabel', array('a' => 'A'));
            $form->expectOnce('submitButtonByLabel', array('Go'));
            
            $page = &new MockSimplePage($this);
            $page->setReturnReference('getFormBySubmitLabel', $form);
            $page->expectOnce('getFormBySubmitLabel', array('Go'));
            $page->setReturnValue('getRaw', 'stuff');
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->clickSubmit('Go'));
            
            $agent->tally();
            $page->tally();
            $form->tally();
        }
        function testDefaultSubmitFormByLabel() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', new MockSimpleHttpResponse($this));
            $agent->expectArgumentsAt(
                    1,
                    'fetchResponse',
                    array('GET', new SimpleUrl('http://this.com/page.html'), array('a' => 'A')));
            $agent->expectCallCount('fetchResponse', 2);
            
            $form = &new MockSimpleForm($this);
            $form->setReturnValue('getAction', false);
            $form->setReturnValue('getMethod', 'get');
            $form->setReturnValue('submitButtonByLabel', array('a' => 'A'));
            
            $page = &new MockSimplePage($this);
            $page->setReturnReference('getFormBySubmitLabel', $form);
            $page->expectOnce('getFormBySubmitLabel', array('Submit'));
            $page->setReturnValue('getRaw', 'stuff');
            $page->setReturnValue('getRequestUrl', new SimpleUrl('http://this.com/page.html'));
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->clickSubmit());
            
            $agent->tally();
            $page->tally();
            $form->tally();
        }
        function testSubmitFormByName() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', new MockSimpleHttpResponse($this));
            
            $form = &new MockSimpleForm($this);
            $form->setReturnValue('getAction', 'handler.html');
            $form->setReturnValue('getMethod', 'post');
            $form->setReturnValue('submitButtonByName', array('a' => 'A'));
            
            $page = &new MockSimplePage($this);
            $page->setReturnReference('getFormBySubmitName', $form);
            $page->expectOnce('getFormBySubmitName', array('me'));
            $page->setReturnValue('getRaw', 'stuff');
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->clickSubmitByName('me'));
            
            $page->tally();
        }
        function testSubmitFormById() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', new MockSimpleHttpResponse($this));
            
            $form = &new MockSimpleForm($this);
            $form->setReturnValue('getAction', 'handler.html');
            $form->setReturnValue('getMethod', 'post');
            $form->setReturnValue('submitButtonById', array('a' => 'A'));
            $form->expectOnce('submitButtonById', array(99));
            
            $page = &new MockSimplePage($this);
            $page->setReturnReference('getFormBySubmitId', $form);
            $page->expectOnce('getFormBySubmitId', array(99));
            $page->setReturnValue('getRaw', 'stuff');
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->clickSubmitById(99));
            
            $page->tally();
            $form->tally();
        }
        function testSubmitFormByImageLabel() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', new MockSimpleHttpResponse($this));
            
            $form = &new MockSimpleForm($this);
            $form->setReturnValue('getAction', 'handler.html');
            $form->setReturnValue('getMethod', 'post');
            $form->setReturnValue('submitImageByLabel', array('a' => 'A'));
            $form->expectOnce('submitImageByLabel', array('Go!', 10, 11));
            
            $page = &new MockSimplePage($this);
            $page->setReturnReference('getFormByImageLabel', $form);
            $page->expectOnce('getFormByImageLabel', array('Go!'));
            $page->setReturnValue('getRaw', 'stuff');
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->clickImage('Go!', 10, 11));
            
            $page->tally();
            $form->tally();
        }
        function testSubmitFormByImageName() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', new MockSimpleHttpResponse($this));
            
            $form = &new MockSimpleForm($this);
            $form->setReturnValue('getAction', 'handler.html');
            $form->setReturnValue('getMethod', 'post');
            $form->setReturnValue('submitImageByName', array('a' => 'A'));
            $form->expectOnce('submitImageByName', array('a', 10, 11));
            
            $page = &new MockSimplePage($this);
            $page->setReturnReference('getFormByImageName', $form);
            $page->expectOnce('getFormByImageName', array('a'));
            $page->setReturnValue('getRaw', 'stuff');
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->clickImageByName('a', 10, 11));
            
            $page->tally();
            $form->tally();
        }
        function testSubmitFormByImageId() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', new MockSimpleHttpResponse($this));
            
            $form = &new MockSimpleForm($this);
            $form->setReturnValue('getAction', 'handler.html');
            $form->setReturnValue('getMethod', 'post');
            $form->setReturnValue('submitImageById', array('a' => 'A'));
            $form->expectOnce('submitImageById', array(99, 10, 11));
            
            $page = &new MockSimplePage($this);
            $page->setReturnReference('getFormByImageId', $form);
            $page->expectOnce('getFormByImageId', array(99));
            $page->setReturnValue('getRaw', 'stuff');
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->clickImageById(99, 10, 11));
            
            $page->tally();
            $form->tally();
        }
        function testSubmitFormByFormId() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', new MockSimpleHttpResponse($this));
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
            $page->setReturnValue('getRaw', 'stuff');
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->submitFormById(33));
            
            $agent->tally();
            $page->tally();
        }
    }
?>