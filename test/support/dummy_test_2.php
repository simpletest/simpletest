<?php
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../../");
    }

    class DummyTestTwo extends SimpleTestCase {
        function DummyTestTwo() {
            $this->SimpleTestCase();
        }
        function testTwo() {
            $this->assertTrue(true, "True");
        }
    }
?>