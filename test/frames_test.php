<?php
    // $Id$
    
    require_once(dirname(__FILE__) . '/../tag.php');
    require_once(dirname(__FILE__) . '/../page.php');
    require_once(dirname(__FILE__) . '/../frames.php');
    
    Mock::generate('SimplePage');
    Mock::generate('SimpleForm');
    
    class TestOfFrameset extends UnitTestCase {
        function TestOfFrameset() {
            $this->UnitTestCase();
        }
        function testTitleReadFromFramesetPage() {
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getTitle', 'This page');
            $frameset = &new SimpleFrameset($page);
            $this->assertEqual($frameset->getTitle(), 'This page');
        }
        function TestHeadersReadFromFramesetByDefault() {
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getHeaders', 'Header: content');
            $page->setReturnValue('getMimeType', 'text/xml');
            $page->setReturnValue('getResponseCode', 401);
            $page->setReturnValue('getTransportError', 'Could not parse headers');
            $page->setReturnValue('getAuthentication', 'Basic');
            $page->setReturnValue('getRealm', 'Safe place');
            
            $frameset = &new SimpleFrameset($page);
            
            $this->assertIdentical($frameset->getHeaders(), 'Header: content');
            $this->assertIdentical($frameset->getMimeType(), 'text/xml');
            $this->assertIdentical($frameset->getResponseCode(), 401);
            $this->assertIdentical($frameset->getTransportError(), 'Could not parse headers');
            $this->assertIdentical($frameset->getAuthentication(), 'Basic');
            $this->assertIdentical($frameset->getRealm(), 'Safe place');
        }
        function testEmptyFramesetHasNoContent() {
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getRaw', 'This content');
            $frameset = &new SimpleFrameset($page);
            $this->assertEqual($frameset->getRaw(), '');
        }
        function testRawContentIsFromOnlyFrame() {
            $page = &new MockSimplePage($this);
            $page->expectNever('getRaw');
            
            $frame = &new MockSimplePage($this);
            $frame->setReturnValue('getRaw', 'Stuff');
            
            $frameset = &new SimpleFrameset($page);
            $frameset->addParsedFrame($frame);
            $this->assertEqual($frameset->getRaw(), 'Stuff');
        }
        function testRawContentIsFromAllFrames() {
            $page = &new MockSimplePage($this);
            $page->expectNever('getRaw');
            
            $frame1 = &new MockSimplePage($this);
            $frame1->setReturnValue('getRaw', 'Stuff1');
            
            $frame2 = &new MockSimplePage($this);
            $frame2->setReturnValue('getRaw', 'Stuff2');
            
            $frameset = &new SimpleFrameset($page);
            $frameset->addParsedFrame($frame1);
            $frameset->addParsedFrame($frame2);
            $this->assertEqual($frameset->getRaw(), 'Stuff1Stuff2');
        }
        function testFieldIsFirstInFramelist() {
            $frame1 = &new MockSimplePage($this);
            $frame1->setReturnValue('getField', null);
            $frame1->expectOnce('getField', array('a'));
            
            $frame2 = &new MockSimplePage($this);
            $frame2->setReturnValue('getField', 'A');
            $frame2->expectOnce('getField', array('a'));
            
            $frame3 = &new MockSimplePage($this);
            $frame3->expectNever('getField');
            
            $page = &new MockSimplePage($this);
            $frameset = &new SimpleFrameset($page);
            $frameset->addParsedFrame($frame1);
            $frameset->addParsedFrame($frame2);
            $frameset->addParsedFrame($frame3);
            
            $this->assertIdentical($frameset->getField('a'), 'A');
            $frame1->tally();
            $frame2->tally();
        }
    }
    
    class TestOfFrameNavigation extends UnitTestCase {
        function TestOfFrameNavigation() {
            $this->UnitTestCase();
        }
        function testStartsWithoutFrameFocus() {
            $page = &new MockSimplePage($this);
            $frameset = &new SimpleFrameset($page);
            $frameset->addParsedFrame($frame);
            $this->assertFalse($frameset->getFrameFocus());
        }
        function testCanFocusOnSingleFrame() {
            $page = &new MockSimplePage($this);
            $page->expectNever('getRaw');
            
            $frame = &new MockSimplePage($this);
            $frame->setReturnValue('getRaw', 'Stuff');
            
            $frameset = &new SimpleFrameset($page);
            $frameset->addParsedFrame($frame);
            
            $this->assertFalse($frameset->setFrameFocusByIndex(0));
            $this->assertTrue($frameset->setFrameFocusByIndex(1));
            $this->assertFalse($frameset->setFrameFocusByIndex(2));
            $this->assertEqual($frameset->getRaw(), 'Stuff');
            $this->assertIdentical($frameset->getFrameFocus(), 1);
        }
        function testCanReadFrameForFurtherFrames() {
            $page = &new MockSimplePage($this);
            
            $frame = &new MockSimplePage($this);
            $frame->setReturnValue('hasFrames', false);
            
            $frameset = &new SimpleFrameset($page);
            $frameset->addParsedFrame($frame);
            
            $this->assertTrue($frameset->hasFrames());
            $this->assertTrue($frameset->setFrameFocusByIndex(1));
            $this->assertIdentical($frameset->hasFrames(), false);
        }
        function testContentComesFromFrameInFocus() {
            $page = &new MockSimplePage($this);
            
            $frame1 = &new MockSimplePage($this);
            $frame1->setReturnValue('getRaw', 'Stuff1');
            
            $frame2 = &new MockSimplePage($this);
            $frame2->setReturnValue('getRaw', 'Stuff2');
            
            $frameset = &new SimpleFrameset($page);
            $frameset->addParsedFrame($frame1);
            $frameset->addParsedFrame($frame2);
            
            $this->assertTrue($frameset->setFrameFocusByIndex(1));
            $this->assertEqual($frameset->getFrameFocus(), 1);
            $this->assertEqual($frameset->getRaw(), 'Stuff1');
            
            $this->assertTrue($frameset->setFrameFocusByIndex(2));
            $this->assertEqual($frameset->getFrameFocus(), 2);
            $this->assertEqual($frameset->getRaw(), 'Stuff2');
            
            $this->assertFalse($frameset->setFrameFocusByIndex(3));
            $this->assertEqual($frameset->getFrameFocus(), 2);
            
            $frameset->clearFrameFocus();
            $this->assertEqual($frameset->getRaw(), 'Stuff1Stuff2');
        }
        function testCanFocusByName() {
            $page = &new MockSimplePage($this);
            
            $frame1 = &new MockSimplePage($this);
            $frame1->setReturnValue('getRaw', 'Stuff1');
            
            $frame2 = &new MockSimplePage($this);
            $frame2->setReturnValue('getRaw', 'Stuff2');
            
            $frameset = &new SimpleFrameset($page);
            $frameset->addParsedFrame($frame1, 'A');
            $frameset->addParsedFrame($frame2, 'B');
            
            $this->assertTrue($frameset->setFrameFocus('A'));
            $this->assertEqual($frameset->getFrameFocus(), 'A');
            $this->assertEqual($frameset->getRaw(), 'Stuff1');
            
            $this->assertTrue($frameset->setFrameFocusByIndex(2));
            $this->assertEqual($frameset->getFrameFocus(), 'B');
            $this->assertEqual($frameset->getRaw(), 'Stuff2');
            
            $this->assertFalse($frameset->setFrameFocus('z'));
            
            $frameset->clearFrameFocus();
            $this->assertEqual($frameset->getRaw(), 'Stuff1Stuff2');
        }
    }
    
    class TestOfFramesetPageInterface extends UnitTestCase {
        var $_page_interface;
        var $_frameset_interface;
        
        function TestOfFramesetPageInterface() {
            $this->UnitTestCase();
            $this->_page_interface = $this->_getPageMethods();
            $this->_frameset_interface = $this->_getFramesetMethods();
        }
        function _getPageMethods() {
            $methods = array();
            foreach (get_class_methods('SimplePage') as $method) {
                if (strtolower($method) == strtolower('SimplePage')) {
                    continue;
                }
                if (strncmp($method, '_', 1) == 0) {
                    continue;
                }
                if (strncmp($method, 'accept', 6) == 0) {
                    continue;
                }
                $methods[] = $method;
            }
            return $methods;
        }
        function _getFramesetMethods() {
            $methods = array();
            foreach (get_class_methods('SimpleFrameset') as $method) {
                if (strtolower($method) == strtolower('SimpleFrameset')) {
                    continue;
                }
                if (strncmp($method, '_', 1) == 0) {
                    continue;
                }
                if (strncmp($method, 'add', 3) == 0) {
                    continue;
                }
                $methods[] = $method;
            }
            return $methods;
        }
        function testFramsetHasPageInterface() {
            $difference = array();
            foreach ($this->_page_interface as $method) {
                if (! in_array($method, $this->_frameset_interface)) {
                    $this->fail("No [$method] in Frameset class");
                    return;
                }
            }
            $this->pass('Frameset covers Page interface');
        }
        function testHeadersReadFromFrameIfInFocus() {
            $frame = &new MockSimplePage($this);
            $frame->setReturnValue('getRequest', 'POST stuff');
            $frame->setReturnValue('getRequestMethod', 'POST');
            $frame->setReturnValue('getRequestUrl', 'http://localhost/stuff');
            $frame->setReturnValue('getRequestData', array('a' => 'A'));
            $frame->setReturnValue('getHeaders', 'Header: content');
            $frame->setReturnValue('getMimeType', 'text/xml');
            $frame->setReturnValue('getResponseCode', 401);
            $frame->setReturnValue('getTransportError', 'Could not parse headers');
            $frame->setReturnValue('getAuthentication', 'Basic');
            $frame->setReturnValue('getRealm', 'Safe place');
            
            $frameset = &new SimpleFrameset(new MockSimplePage($this));
            $frameset->addParsedFrame($frame);
            $frameset->setFrameFocusByIndex(1);
            
            $this->assertIdentical($frameset->getRequest(), 'POST stuff');
            $this->assertIdentical($frameset->getRequestMethod(), 'POST');
            $this->assertIdentical($frameset->getRequestUrl(), 'http://localhost/stuff');
            $this->assertIdentical($frameset->getRequestData(), array('a' => 'A'));
            $this->assertIdentical($frameset->getHeaders(), 'Header: content');
            $this->assertIdentical($frameset->getMimeType(), 'text/xml');
            $this->assertIdentical($frameset->getResponseCode(), 401);
            $this->assertIdentical($frameset->getTransportError(), 'Could not parse headers');
            $this->assertIdentical($frameset->getAuthentication(), 'Basic');
            $this->assertIdentical($frameset->getRealm(), 'Safe place');
        }
        function testAbsoluteUrlsComeFromBothFrames() {
            $page = &new MockSimplePage($this);
            $page->expectNever('getAbsoluteUrls');
            
            $frame1 = &new MockSimplePage($this);
            $frame1->setReturnValue(
                    'getAbsoluteUrls',
                    array('http://www.lastcraft.com/', 'http://myserver/'));
            
            $frame2 = &new MockSimplePage($this);
            $frame2->setReturnValue(
                    'getAbsoluteUrls',
                    array('http://www.lastcraft.com/', 'http://test/'));
            
            $frameset = &new SimpleFrameset($page);
            $frameset->addParsedFrame($frame1);
            $frameset->addParsedFrame($frame2);
            $this->assertEqual(
                    $frameset->getAbsoluteUrls(),
                    array('http://www.lastcraft.com/', 'http://myserver/', 'http://test/'));
        }
        function testRelativeUrlsComeFromBothFrames() {
            $frame1 = &new MockSimplePage($this);
            $frame1->setReturnValue(
                    'getRelativeUrls',
                    array('/', '.', '/test/', 'goodbye.php'));
            
            $frame2 = &new MockSimplePage($this);
            $frame2->setReturnValue(
                    'getRelativeUrls',
                    array('/', '..', '/test/', 'hello.php'));
            
            $page = &new MockSimplePage($this);
            $page->expectNever('getRelativeUrls');
            
            $frameset = &new SimpleFrameset($page);
            $frameset->addParsedFrame($frame1);
            $frameset->addParsedFrame($frame2);
            $this->assertEqual(
                    $frameset->getRelativeUrls(),
                    array('/', '.', '/test/', 'goodbye.php', '..', 'hello.php'));
        }
        function testLabelledUrlsComeFromBothFrames() {
            $frame1 = &new MockSimplePage($this);
            $frame1->setReturnValue('getUrlsByLabel', array('goodbye.php'), array('a'));
            
            $frame2 = &new MockSimplePage($this);
            $frame2->setReturnValue('getUrlsByLabel', array('hello.php'), array('a'));
            
            $frameset = &new SimpleFrameset(new MockSimplePage($this));
            $frameset->addParsedFrame($frame1);
            $frameset->addParsedFrame($frame2);
            $this->assertEqual(
                    $frameset->getUrlsByLabel('a'),
                    array('goodbye.php', 'hello.php'));
        }
        function testUrlByIdComesFromFirstFrameToRespond() {
            $frame1 = &new MockSimplePage($this);
            $frame1->setReturnValue('getUrlById', 'four.php', array(4));
            $frame1->setReturnValue('getUrlById', false, array(5));
            
            $frame2 = &new MockSimplePage($this);
            $frame2->setReturnValue('getUrlById', false, array(4));
            $frame2->setReturnValue('getUrlById', 'five.php', array(5));
            
            $frameset = &new SimpleFrameset(new MockSimplePage($this));
            $frameset->addParsedFrame($frame1);
            $frameset->addParsedFrame($frame2);
            $this->assertEqual($frameset->getUrlById(4), 'four.php');
            $this->assertEqual($frameset->getUrlById(5), 'five.php');
        }
        function testReadUrlsFromFrameInFocus() {
            $frame1 = &new MockSimplePage($this);
            $frame1->setReturnValue('getAbsoluteUrls', array('a'));
            $frame1->setReturnValue('getRelativeUrls', array('r'));
            $frame1->setReturnValue('getUrlsByLabel', array('l'));
            $frame1->setReturnValue('getUrlById', array('i'));
            
            $frame2 = &new MockSimplePage($this);
            $frame2->expectNever('getAbsoluteUrls');
            $frame2->expectNever('getRelativeUrls');
            $frame2->expectNever('getUrlsByLabel');
            $frame2->expectNever('getUrlById');

            $frameset = &new SimpleFrameset(new MockSimplePage($this));
            $frameset->addParsedFrame($frame1, 'A');
            $frameset->addParsedFrame($frame2, 'B');
            $frameset->setFrameFocus('A');
            
            $this->assertEqual($frameset->getAbsoluteUrls(), array('a'));
            $this->assertEqual($frameset->getRelativeUrls(), array('r'));
            $this->assertEqual($frameset->getUrlsByLabel('label'), array('l'));
            $this->assertEqual($frameset->getUrlById(99), array('i'));
        }
        function testFindingForms() {
            $finders = array(
                    'getFormBySubmitLabel', 'getFormBySubmitName',
                    'getFormBySubmitId', 'getFormByImageLabel',
                    'getFormByImageName', 'getFormByImageId', 'getFormById');
            $forms = array();
            
            $frame = &new MockSimplePage($this);
            for ($i = 0; $i < count($finders); $i++) {
                $forms[$i] = &new MockSimpleForm($this);
                $frame->setReturnReference($finders[$i], $forms[$i], array('a'));
            }

            $frameset = &new SimpleFrameset(new MockSimplePage($this));
            $frameset->addParsedFrame(new MockSimplePage($this), 'A');
            $frameset->addParsedFrame($frame, 'B');
            for ($i = 0; $i < count($finders); $i++) {
                $method = $finders[$i];
                $this->assertReference($frameset->$method('a'), $forms[$i]);
            }
           
            $frameset->setFrameFocus('A');
            for ($i = 0; $i < count($finders); $i++) {
                $method = $finders[$i];
                $this->assertNull($frameset->$method('a'));
            }
           
            $frameset->setFrameFocus('B');
            for ($i = 0; $i < count($finders); $i++) {
                $method = $finders[$i];
                $this->assertReference($frameset->$method('a'), $forms[$i]);
            }
        }
        function testSettingAllFrameFieldsWhenNoFrameFocus() {
            $frame1 = &new MockSimplePage($this);
            $frame1->expectOnce('setField', array('a', 'A'));
            $frame1->expectOnce('setFieldById', array(22, 'A'));
            
            $frame2 = &new MockSimplePage($this);
            $frame2->expectOnce('setField', array('a', 'A'));
            $frame2->expectOnce('setFieldById', array(22, 'A'));
            
            $frameset = &new SimpleFrameset(new MockSimplePage($this));
            $frameset->addParsedFrame($frame1, 'A');
            $frameset->addParsedFrame($frame2, 'B');
            
            $frameset->setField('a', 'A');
            $frameset->setFieldById(22, 'A');
            $frame1->tally();
            $frame2->tally();
        }
        function testOnlySettingFieldFromFocusedFrame() {
            $frame1 = &new MockSimplePage($this);
            $frame1->expectOnce('setField', array('a', 'A'));
            $frame1->expectOnce('setFieldById', array(22, 'A'));
            
            $frame2 = &new MockSimplePage($this);
            $frame2->expectNever('setField');
            $frame2->expectNever('setFieldById');
            
            $frameset = &new SimpleFrameset(new MockSimplePage($this));
            $frameset->addParsedFrame($frame1, 'A');
            $frameset->addParsedFrame($frame2, 'B');
            $frameset->setFrameFocus('A');
            
            $frameset->setField('a', 'A');
            $frameset->setFieldById(22, 'A');
            $frame1->tally();
        }
        function testOnlyGettingFieldFromFocusedFrame() {
            $frame1 = &new MockSimplePage($this);
            $frame1->setReturnValue('getField', 'f', array('a'));
            $frame1->setReturnValue('getFieldById', 'i', array(7));
            
            $frame2 = &new MockSimplePage($this);
            $frame2->expectNever('getField');
            $frame2->expectNever('getFieldById');
            
            $frameset = &new SimpleFrameset(new MockSimplePage($this));
            $frameset->addParsedFrame($frame1, 'A');
            $frameset->addParsedFrame($frame2, 'B');
            $frameset->setFrameFocus('A');
            
            $this->assertIdentical($frameset->getField('a'), 'f');
            $this->assertIdentical($frameset->getFieldById(7), 'i');
        }
    }
?>