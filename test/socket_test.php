<?php
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
        function testOpenBadSocket() {
            $socket = new Socket("bad_url");
            $this->assertTrue($socket->isError(), "Error [" . $socket->getError(). "]");
        }
        function testOpenGoodSocket() {
            $socket = new Socket("localhost");
            $this->assertFalse($socket->isError(), "Error [" . $socket->getError(). "]");
        }
    }
?>