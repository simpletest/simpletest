<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'socket.php');

    class TestOfStickyError extends UnitTestCase {
        function TestOfStickyError() {
            $this->UnitTestCase();
        }
        function testSettingError() {
            $error = new StickyError();
            $this->assertFalse($error->isError());
            $error->_setError("Ouch");
            $this->assertTrue($error->isError());
            $this->assertEqual($error->getError(), "Ouch");
        }
        function testClearingError() {
            $error = new StickyError();
            $error->_setError("Ouch");
            $this->assertTrue($error->isError());
            $error->_clearError();
            $this->assertFalse($error->isError());
        }
    }

    class TestOfSocket extends UnitTestCase {
        function TestOfSocket() {
            $this->UnitTestCase();
        }
        function testBadSocket() {
            $socket = @new SimpleSocket("bad_url", 111);
            $this->assertTrue($socket->isError(), "Error [" . $socket->getError(). "]");
            $this->assertFalse($socket->isOpen());
            $this->assertFalse($socket->write("A message"));
        }
    }
?>