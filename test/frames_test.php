<?php
    // $Id$
    
    require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '../page.php');
    require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '../frames.php');
    
    Mock::generate('SimplePage');
    
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
        function TODO_testRawContentIsFromFrame() {
            $page = &new MockSimplePage($this);
            $page->expectNever('getRaw');
            
            $frame = &new MockSimplePage($this);
            $frame->setReturnValue('getRaw', 'Stuff');
            
            $frameset = &new SimpleFrameset($page);
            //$frameset->addFrame($frame);
            $this->assertEqual($frameset->getRaw(), 'Stuff');
        }
    }
?>