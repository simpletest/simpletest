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
            $socket = new Socket("bad_url", 111);
            $this->assertTrue($socket->isError(), "Error [" . $socket->getError(). "]");
            $this->assertFalse($socket->write("A message"));
        }
        function testWriteAndRead() {
            $socket = new Socket("www.lastcraft.com", 80);
            $this->assertFalse($socket->isError(), "Error [" . $socket->getError(). "]");
            $this->assertTrue($socket->write("GET www.lastcraft.com HTTP/1.0\r\n"));
            $socket->write("Host: localhost\r\n");
            $socket->write("Connection: close\r\n\r\n");
            $this->assertEqual($socket->read(8), "HTTP/1.1");
        }
    }
?>