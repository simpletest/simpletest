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
?>