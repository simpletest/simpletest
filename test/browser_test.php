<?php
    // $Id$
    
    if (!defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', '../');
    }
    require_once(SIMPLE_TEST . 'browser.php');
    require_once(SIMPLE_TEST . 'http.php');
    Mock::generate('SimpleHttpRequest');
    Mock::generate('SimpleHttpResponse');
    Mock::generate('SimpleHttpHeaders');
    Mock::generate('SimplePage');
    Mock::generate('SimpleFetcher');
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
            
            $agent = &new MockSimpleFetcher($this);
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
?>