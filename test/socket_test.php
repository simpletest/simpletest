<?php
    // $Id$
    
    require_once(dirname(__FILE__) . '/../socket.php');
    
    Mock::generate('SimpleSocket');

    class TestOfStickyError extends UnitTestCase {
        function TestOfStickyError() {
            $this->UnitTestCase();
        }
        function testSettingError() {
            $error = new StickyError();
            $this->assertFalse($error->isError());
            $error->_setError('Ouch');
            $this->assertTrue($error->isError());
            $this->assertEqual($error->getError(), 'Ouch');
        }
        function testClearingError() {
            $error = new StickyError();
            $error->_setError('Ouch');
            $this->assertTrue($error->isError());
            $error->_clearError();
            $this->assertFalse($error->isError());
        }
    }

    class TestOfSocketScribe extends UnitTestCase {
        function TestOfSocketScribe() {
            $this->UnitTestCase();
        }
        function testDecoration() {
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue('isError', true);
            $socket->setReturnValue('getError', 'Ouch');
            $socket->setReturnValue('write', true);
            $socket->expectOnce('write', array('Hello'));
            $socket->setReturnValue('read', 'Stuff');
            $socket->expectOnce('read', array(200));
            $socket->setReturnValue('isOpen', true);
            $socket->setReturnValue('close', true);
            
            $scribe = &new SimpleSocketScribe($socket);
            $this->assertTrue($scribe->isError());
            $this->assertEqual($scribe->getError(), 'Ouch');
            $this->assertTrue($scribe->write('Hello'));
            $this->assertEqual($scribe->read(200), 'Stuff');
            $this->assertTrue($scribe->isOpen());
            $this->assertTrue($scribe->close());
            
            $socket->tally();
        }
        function testCaptureOnWrite() {
            $socket = &new MockSimpleSocket($this);
            $socket->setReturnValue('write', true);
            
            $scribe = &new SimpleSocketScribe($socket);
            $scribe->write('First');
            $scribe->write('Second');
            $this->assertEqual($scribe->getSent(), 'FirstSecond');
        }
    }
?>